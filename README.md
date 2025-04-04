# Disposable Extended Pack v3

phpVMS v7 module for Extended VA features

> [!IMPORTANT]
> Minimum required phpVMS v7 version is `phpVms 7.0.52-dev.g0421186c64` / 05.JAN.2025

> [!TIP]
> * Module supports **only** php8.1+ and laravel10
> * _php8.0 and laravel9 compatible latest version: v3.3.1_
> * _php7.4 and laravel8 compatible latest version: v3.0.19_

Module blades are designed for themes using **Bootstrap v5.x** and FontAwesome v5.x (not v6) icons.

Using this module along with *Disposable Basic* and *Disposable Theme* is advised but not mandatory. This module pack aims to cover extended needs of any Virtual Airline with some new features, widgets and backend tools. Provides;

* Tours (with Awards and a tracking Widget)
* Free Flights (with full SimBrief integration)
* Maintenance System (can be extended by Disposable Basic module)
* Market (Pilot Shop)
* Monthy Flight Assignments
* NOTAMs
* Configurable per flight dynamic expenses (Catering, Parking, Landing, Terminal Services Fees etc)
* Configurable per flight dynamic income (Duty Free and Cabin Bouffet Sales)
* Some static pages (About Us, Rules & Regulations, Ops Manual, Landing Rates)
* Handy administrative functions
* CRON based automated database cleanup features
* API endpoints to support data display at landing pages (for monthly assignments and tours)

## Compatibility with other addons

This addon is fully compatible with phpVMS v7 and it will work with any other addon, specially acars softwares which are 100% compatible with phpVMS v7 too.  

If the acars solution you are using is not compatible with phpVMS v7, then it is highly probable that you will face errors over and there. In this case, please speak with your addon provider not me 'cause I can not fix something I did not broke, or I can not cover somebody else's mistakes, poor compatibility problems etc.

If an addon is fully compatible with phpVMS v7 and needs/uses some custom features, then I can work on this module to support that addon's special needs too.

As of date, module supports vmsACARS.

## Installation and Updates

* Manual Install : Upload contents of the package to your phpvms root `/modules` folder via ftp or your control panel's file manager
* GitHub Clone : Clone/pull repository to your phpvms root `/modules/DisposableSpecial` folder
* PhpVms Module Installer : Go to admin -> addons/modules , click Add New , select downloaded file then click Add Module
* Go to admin > addons/modules enable the module
* Go to admin > dashboard (or /update) to trigger module migrations
* When migration is completed, go to admin > maintenance and clean `application` cache

> [!WARNING]
> :information_source: *There is a known bug in v7 core, which causes an error/exception when enabling/disabling modules manually. If you see a server error page or full stacktrace debug window when you enable a module just close that page and re-visit admin area in a different browser tab/window. You will see that the module is enabled and active, to be sure just clean your `application` cache*

### Update (from v3.xx to v3.yy)

Just upload updated files by overwriting your old module files, visit /update and clean `application` cache when update process finishes.

### Update (from v2.xx series to v3.xx)

Below order and steps are really important for proper update from old modules to new combined module pack

> [!CAUTION]
> :warning: **There is no easy going back to v2 series once v3 is installed !!!** :warning:  
> **Backup your database tables and old module files before this process**  
> **Only database tables starting with `turksim_` is needed to be backed up**

* From admin > addons/modules **DISABLE** all old TurkSim modules
* From admin > addons/modules **DELETE** all old TurkSim modules
* Go to admin > maintenance and clean `all` cache
* Install Disposable Special module (by following installation procedure)

After successfull installation, followed by last application cache cleaning

* Go to phpvms admin > awards and re-assign new classes to your old tour awards.

This last step is important, skipping this will result errors during pirep accept process! (Either manual or automatic, they will fail)

## Module links and routes

Module does not provide auto links to your phpvms theme, Disposable Theme v3 has some built in menu items but in case you need to manually adjust or use a different theme/menu, below are the routes and their respective url's module provide

Named Routes and Url's

```php
DSpecial.tours          /dtours            // Tours index page
DSpecial.tour           /dtours/WT21       // Tour details page, needs a tour {code} to run

DSpecial.assignments    /dassignments      // Monthly Assignments index page
DSpecial.freeflight     /dfreeflight       // (Personal) Free Flight index page
DSpecial.maintenance    /dmaintenance      // Fleet Maintenance index page
DSpecial.market         /dmarket           // Market index page
DSpecial.market.show    /dmarket/1         // Personal items bought from market, needs a user {id} to run
DSpecial.missions       /dmissions         // Missions index page
DSpecial.notams         /dnotams           // Notams index page

DSpecial.ops_manual     /dopsmanual        // Operations Manual page (partly db driven, mostly static)
DSpecial.landing_rates  /dlandingrates     // Landing Rates page (Static content)
DSpecial.about_us       /daboutus          // About US page (Static content, public)
DSpecial.rules_regs     /drulesandregs     // Rules and Regulations page (Static content, public)
```

Usage examples;

```html
<a class="nav-link" href="{{ route('DSpecial.tours') }}" title="Tours">
  Tours
  <i class="fas fa-paper-plane mx-1"></i>
</a>

<a class="nav-link" href="{{ route('DSpecial.tour', [$tour->code]) }}" title="Tour Details">
  {{ $tour->name }}
  <i class="fas fa-link mx-1"></i>
</a>

<a class="nav-link" href="/dtours" title="Tours">
  Tours
  <i class="fas fa-paper-plane mx-1"></i>
</a>

<a class="nav-link" href="/dtours/{{ $tour->code}}" title="Tour Details">
  {{ $tour->name }}
  <i class="fas fa-link mx-1"></i>
</a>
```
## API Endpoints

Module offers below endpoints for API Access with authorization, so data can be placed on landing pages easily. Check module admin page to define your service key, which is needed for authorization.

### Endpoints

```php
/dsapi/assignments  // Monthly Assignments
/dsapi/tours // Tours
```
### Header Options and Example Request

```php
Content-Type:application/json
x-service-key:{your service key}
x-a-year:2023 // by default api return current year, only use if you need to get other years
x-a-month:5 // by default api returns current month, only use if you need to get other months
```

```php
$service_key = "YOUR SERVICE KEY";
$url = "https://your-phpvms-v7-site.com/dsapi/assignments";
// This will give you current months assignments
$headers = [
    'Content-Type:application/json',
    'x-service-key:' . $service_key,
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$json = curl_exec($ch);
if (!$json) {
    echo curl_error($ch);
}

curl_close($ch);
$assignments = json_decode($json, true);

echo $assignments;
```

```php
$service_key = "YOUR SERVICE KEY";
$url = "https://your-phpvms-v7-site.com/dsapi/tours";
// This will give you current and upcoming active tours with participant and leg counts, also leg/airport details are available
$headers = [
    'Content-Type:application/json',
    'x-service-key:' . $service_key,
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$json = curl_exec($ch);
if (!$json) {
    echo curl_error($ch);
}

curl_close($ch);
$tours = json_decode($json, true);

echo $tours;
```

You can use Postman or Apidog (or a similar tool) to test api access easily and see returned data for landing page development.  

## Operational Usage and Provided Features

Below you can find some details about how this module is designed and how it behaves according to your configuration options.

### How to define Tours ?

First you need to define your tour from *Admin > Disposable Special*, then you need to add each tour leg from *PhpVms Admin > Flights* interface. When inserting your flights, the tour's legs in particular you need to use the tour code you defined as the route code and you need to define the legs in order. This is a little bit common knowledge about the tours and I think you already know that very well.

We are able to use different validity dates for the tours and the legs. Tours must have start and end dates, but the flights (legs) do not have to but if you want the legs to be flown between particular dates then  you can define each legs validity while inserting (or editing) the legs.

Imagine you are defining a tour for Formula1 Season 2021, your tour should start before the first race and end before or just after the last race. Also you want your pilots to fly the tour legs according to the race schedule, they will carry teams, fans and maybe cargo from race to race ! Then you need to define each legs validity period too ;) This will be a really hard tour though but it will be fun to complete as the races progress.

This logic may be extended as you wish.

If you have a multiple airline setup, then you can setup your tours for your airlines too. Checks will done according to that. Also two Award Classes are provided, one for Open/Generic Tours (no airline defined) one for Airline Tours (with airline checks).

Tours can be limited by market powered "Tour Token"s, a token can be defined/used for multiple tours (but a tour can not have multiple tokens). Users without required tokens can not visit the tour details pages at all.  

Tour Management page offers quick solutions for deleting tour legs, making legs active/passive and easy access for editing individual legs, also "ownership" logic is implemented both manually and via nightly cron.  

### How to use NOTAMs ?

Well, it is totaly up to you. They will be displayed close to real life NOTAM format, you can use them as News like or inform your pilots about procedures for a special airport etc. Just check module admin page for Notam management.

*Replacing Notam* is used to override a previous notam. When used C0018/21 NOTAM**R 0001** is added to Notam Ident. (**R 0001** means 0018 is replacing 0001)

**A** is used for Airport Notams, **C** is used for Company Notams. I just removed the Q code from the equation for my own mental health :)

### How can I use Widgets provided ?

Simple, just use standard Laravel call for widgets, provided widgets are available as below;

```php
@widget('DSpecial::Assignments')
@widget('DSpecial::FeaturedItem')
@widget('DSpecial::Missions')
@widget('DSpecial::Notams')
@widget('DSpecial::TourProgress')
@widget('DSpecial::UserItems')
```  

**Assignments** widget has one config option called `'user'` which can be used to display a specific user's progres instead of current user.

* `'user'` can be a user's id (like `$user->id` or `4`)

**Tour Progress** widget has two config options called `'user'` and `'warn'`  

* `'user'` can be a user's id (lie `$user->id` or `3`) and will force the widget to display that user's progress
* `'warn'` can be any number of days like `30` and it will change the progress bar color according to Tour's end date (default is 14 days)

Widget will show progress with yellow (warning) color until the tour is finished and turns to green (success). However if the legs are not flown in correct order it will turn to red (danger) and will not return to green/yellow again.

This also applies to Tour Award classes. If a pilot brokes the leg order, to get the award then those faulty legs should be rejected and must be re-flown.

**Missions** widget has only one setting called `'user'` and allows to be the user_id defined (for profile placement).

Widget will display active/valid missions of a user if there are any, if none present it will not be visible at all.  

**Notams** widget can be configured to display users current location notams or specific notams for an airport or airline.

* `'count'` can be any number (like `50`) to pick latest *EFFECTIVE* specified number of notams
* `'user'` can be either `true` or `false` to check user's current location (default is `false`)
* `'airport'` can be an airport id (like `$airport->id` or `BIKF`) to check only specified airport notams
* `'airline'` can be an airline id (like `$airline->id` or `18`) to check specified airline's company notams

User and airport options can not be used together due to nature of the selection (they are both airport based), rest can be combined

* `['count' => 20, 'airline' => $airline->id, 'airport' => $hub->id]` this combination will display 20 notams of selected airline for specified airport

Widget will **always** display **effective** notams, config options can not change this behavior.

**Featured Item** widget has no settings, it will randomly pick an active market item which the user does not own. If no items are found, it will not be visible.

**User Items** widget will return the items owned by that user ordered by item name. Blade file of the widget needs to be styled according to your theme/skin and placed properly, shipped version will display items with images in cards.

* `'user'` should be a user's id (like `$user->id` or `4`)

### Dynamic Expenses

I tried to make them as close to the real world tariffs as possible, for most of them the calculation formulas are real but in some cases I had to use some non-realistic values 'cause we do not have them in our sim world :( For example when you land an airport, the landing fee is calculated with your aircraft's MTOW but we may not have defined a Maximum TakeOff Weight for it yet. In this case your actual Landing Weight is used. Or in a case where the great circle distance can not be calculated, the actual flight distance or worst the flight time is being used for Air Traffic Services fee calculation.

Settings are simple, if it is not just an enable/disable checkbox then you need to select the value you want to use for that calculation, here are their meanings;

* cap : Capacity (Aircraft total capacity)
* load : Actual Passenger (or Cargo) load
* lw : Actual Landing Weight
* tow : Actual Take Off Weight
* mtow : Maximum Take Off Weight

If you want to go realistic, chose cap and mtow as the authorities do. Always the bigger one, if you want more dynamic and flexible values you can chose cap or lw/tow etc.

Base values are ok for Euro and USD (since they are pretty close to each other, there will be no surprises about the generated monetary amounts). But if you are using something different as your phpVMS currency, I kindly suggest adjusting all base prices according to your needs. *"Unit Rate"* is the monetary amount being used in the calculations, imagine it like the per pax catering price including hot meal, soft drinks etc. Or the amount being used while doing calculations with the weights etc.

### Dynamic Income

Currently only Duty Free and Cabin Bouffet Sales are possible, they can be configured for flight types like int (international) or dom (domestic). Also item prices and airline profit percentage can be defined. Rest is pretty much automatic and dynamic, for each passenger flight module will determine a random number of customers and they will buy randomly picked items. So if you are lucky enough, you may gain a nice profit from in flight sales.

### Maintenance System

Ok, I know there are a lot of settings for this secion. There are reasons for that, trust me on that. You can simply enable/disable per flight checks which are *Hard Landing*, *Soft Landing*, *Tail Strike* and *Engine/Wing Strike*. Also the main setting for *Aircraft State Control* is important 'cause it enables/disables aircraft availability during maintenance operations.

Imagine a scenario, a pilot makes a hard landing *Aircraft State Control* and *Hard Landling Check* is enabled with a default *Generic Check Duration* (1 Hour). This will result both hard landing check expenses being applied to that pirep (and to pilot if you chose so) and most importantly that particular aircraft will NOT be available for flight until maintenance finishes. You can of course manually finish any ongoing checks but do not be surprised if pilots start complaining :)

That *Aircraft State Control* setting also effects admin executed main checks, though you can always start and finish a maintenance from the same page but it is how it works. A ring to rule them all, opps a setting to control them all.

Also there are some flight hour and cycle definitions for main checks, you can use them for all your fleet or you can go crazy and define realistic figures for each ICAO Type you have. Like an extended period for C172 but a realistic period for B738 etc. It is up to you, if you are using one or two similar types, then using the main settings would be practical but for a larger fleet, using [Disposable Basic](https://github.com/FatihKoz/DisposableBasic) and defining ICAO type specific periods would be much realistic.

When you first install the update (or the module first time), your pireps will be read and a starting point for maintenance will be created. From that moment on, every accepted pirep will increase/decrease some values. Like a hard landing hits kindly on the current state of the aircraft (according to the landing rate of course) but a nice landing will make barely noticable impact.  

Upon pirep retrieval; Line Check and A/B/C checks will start automatically according to defined times/limits and main settings if required, VA staff can finish them manually if the aircraft in question is needed urgently. Otherwise cron will handle the automation. Maintenance costs will be slightly discounted on Hubs (more on Aircraft's own hub/base, less on other Hubs), thus "Missions" page will offer some more flights to bring aircraft back to their bases for maintenance purposes.

Since everything is dynamic, maintenance costs are dynamic too. Default base price offered is ok for KG and USD/EURO region. If you are using LBS or using another currency it may not fit your expectations well. Technically the heavier the aircraft (MTOW or last TakeOff Weight if you do not define MTOW at aircraft level), the expensive the maintenance becomes, also the hardness of the landing or the tailstrike effects the price. Imagine same ICAO Type or same aircraft landing with -513 ft/min and then with -627 ft/min, prices will not be the same.

Even though vmsAcars is not reporting TakeOff pitch and roll, module is able to check them too. Currently only landing phase checks will be working.

By design, maintenance actions are checked by cron every 5 minutes. So if an aircraft is under maintenance, it will be released to service with maximum 5 minutes delay compared to published release time.

### Market

Allows you to list items as you wish which pilots can buy and spend their cash. When enabled per item, a discord notification is sent to admin/staff webhook (defined in Dispo Special main settings). Also each item can have a special/custom notes area which only the owners of those items can see. So in theory you can add a link, or a special info there for the owners to see/use.

This is not a full scale shopping cart system, it just enables basic features like buying an item or gifting it to another pilot. How you use it is up to you after all, you can have some special liveries being sold, or training sessions can be arranged when a user buys a specific item etc.

To keep the virtual money inside v7 economics, each item should have a dealer, as in our system dealers are your airlines. You can sell a training with Airline A, a livery with Airline B. Airline/Dealer of the item sold will gain money and this will be visible in financial reports. (Also transactions will be visible in pilot journals for tracking)  

Market comes with two widgets for displaying user owned items and providing a featured item at dashboard (or any other location with login protection).  

Market also provides a category for "Tour Tokens" to allow tour access to be bought by pilots/users before participating in them. Also Tour feature is updated to support automation of this process.  

### Missions

This system is mainly designed to list aircraft parked at destinations other than their bases (Hubs), it offers pilots some flights to bring them back. Pilots can pick a mission flight and then fly it for getting some reward/bonus payments if they wish to. Also system will list some maintenance required aircraft, which needs to be flown back to base for reduced costs.  Due to the competition logic of this section, selecting a maintenance mission will NOT remove it from the list, another pilot can select it too. If aircraft use restrictions are in place, only one pilot can use the aircraft, making it a little bit more competitive.  

Flight selection is based on subfleet <> flight relationship, if they are not defined or there are no flights, then an airline based check is done. If there are no flights found, then a free flight is required.  Pilots will be only see the aircraft which they can operate, according to v7 settings (by rank or by type-rating). If there are no restrictions defined for pilots then all the fleet gets checked.  

_Missions feature is still under development and may have logical changes in the future (like having admin/staff defined personal missions etc.)_  

### Monthly Flight Assignments

This system relies heavily on your flight structure and database records. The settings are pretty basic, it also considers your phpVMS settings too. Auto assignments requires cron to be running, if somehow it fails or you wish to manually trigger the process it is possible to do so.

Most critical part is assigning subfleets to flights, if you have a flexible/relaxed setup where flights have no subfleet definitions then `Use Prefered ICAO Types` setting will not work at all and most probably it will disable itself during assignment process. Likewise phpVMS setting `Restrict Aircraft to Ranks` will have no effect for assignments in this setup.

The worst scenario is, having some leftover data in some database tables and also trying to have a mixed setup (like %50 of the flights have subfleets, rest free etc). In this scenario some users may have assignments, some not! Will try to find a way to overcome this without reducing capabilities and keeping the performance level same.

Also if you plan to use `Average Flight Times` option, then setting a logical margin is important. Setting a margin of for example 120 mins (2 hours) will work of course but it will simply disable the logic behind using avg flight times of a pilot. Imagine a user, with an avg flight time of 2 hours, this means that personally he/she is not prefering to fly longer flights. With a margin of 120 minutes, you will be kindly forcing that user to have an assigment flight with for example 3 hours and 50 minutes! Or maybe a quick hop with 30 minutes only. I personally prefer having the margin set to maximum 45 minutes or 30 minutes. If a flight is not found within user's flight time range (avg +/- margin) then code doubles the margin and re-checks (avg +/- 2x margin).

If you have multiple airlines in your setup, code tries to use the same airline between city pairs and only attempts to change the airline in hubs. Also it is possible to force assignments to a pilot's company. In case you have multiple airlines in your setup and want to display all your flights to pilots (phpvsm restrict to airline setting disabled) but assign flights only from his/her company you can enable module's "Use Pilot Company" setting.

Admins can delete and re-assing monthy flights of users, there is a button for this at user profile (of Disposable Theme). You can check the code and use the same route/button in your own theme too.

As an additional option, admins can select "Always Return to Home" (_which will try to find flights returning to base/home airport for each even leg_) and "Last Flight Returns Home" (_which will try to return home for the last assignment of month_). As one can expect, both options can not run at the same time and this logic will not work with odd number of assignment flights (_like 3,5,7 etc._)

## Duplicating Module Blades/Views

Technically all blade files should work with your template but they are mainly designed for Bootstrap v5.* compatible themes. So if something looks weird in your template then you need to edit the blades files. I kindly suggest copying/duplicating them under your theme folder and do your changes there, directly editing module files will only make updating harder for you.

All Disposable Modules are capable of displaying customized files located under your theme folders;

* Original Location : phpvms root `/modules/DisposableSpecial/Resources/views/tours/some_file.blade.php`
* Target Location   : phpvms root `/resources/views/layouts/YourTheme/modules/DisposableSpecial/tours/some_file.blade.php`

As you can see from the above example, filename and sub-folder location is not changed. We only copy a file from a location to another and have a copied version of it.  
If you have duplicated blades and encounter problems after updating the module or after editing, just rename them to see if the updated/provided original works fine.

## License Compatibility & Attribution Link

As per the license, **addon name should be always visible in all pages**. It is best placed in the footer without a logo to save space but link **SHOULD BE** always visible.
```html
Powered by <a href="https://www.phpvms.net" target="_blank">phpVMS v7</a> & <a href="https://github.com/FatihKoz" target="_blank">DH Addons</a>
```
or
```html
Enhanced by <a href="https://github.com/FatihKoz" target="_blank">DH Addons</a>
```
_Not providing attribution link will result in removal of access and no support is provided afterwards._

## Known Bugs / Problems

* SmartCars v3 users reported problems with some of the widgets of Disposable Basic, root cause is SC3 being not fully phpVMS v7 compatible yet and not sending proper data. So it is highly probable that some features of this module may fail when SC3 Beta is in use too. With latest improvements done to SC3 implementation incompatibilities are reduced but still it may behave different than expected. Please follow changes/updates of SC3 modules being develop by other devs.
* Notam Management airport dropdown does not select already assigned/saved airport! Therefore still using old logic and not switched to ajax search.  

## Release / Update Notes

30.MAR.25

* Updated Missions view (fixed Flight Details button/link)  

08.MAR.25

* Updated Missions (automated flight selection criteria)  

28.FEB.25

* Fixed Misson Flight rewards (aircraft being used must be the same with the mission as expected)  

27.FEB.25

* Improved Maintenance logic, provided automation for Line Checks and A/B/C Checks (works with Pirep events)  
* Improved Missions, added a widget and lists for selection, also a frontend page for easy listing and management
* Added Mission Flight rewards (like Assignments and Random Flights)

01.FEB.25

* Added ability for exporting tour legs (as csv files), single leg deletion and tour deletion (only available when a tour has no legs)

26.JAN.25

* Improved Tour Legs handling via module admin page (added compatibility for latest v7 changes)

25.JAN.25

* Added version info to module admin area  

12.JAN.25

* Version rounding and required minimum phpVMS version change

16.DEC.24

* Fixed the issue with assignment triggering rights (staff and admin)

01.DEC.24

* Fixed an issue with user based assignments manual triggering (staff was not able to do it)  
* Fixed weight based expenses not working (after phpvms aircraft weight changes as expected)  

15.NOV.24

* Turkish language support _Requires latest dev as of 15.NOV.24_  
* Fixed new user discord notification  

24.SEP.24

* Added two new options to monthly assignments (Always return home, Last flight returns home)

17.SEP.24

* Reverted back module view path changes with a slight improvement for active theme
* Updated module.json for future development
* Added manual placement protection for returning leftover/non-flown aircraft to their bases (3 days)
* Added limits to market items, now they can be sold according to stock/limits
* Added some filtering options for tours page (frontend)  
  _both features include blade/view changes_  

17.AUG.24

* Added ability to filter Tours by assigned subfleets
* Added limit to Market Items, default is 0 (unlimited owners/buyers)  
  _both features include blade/view changes_

30.JUN.24

* Improved Tour details page, added bid/simbrief buttons
* Improved Tour reports page, added pilot based flown order for easy problem identification
* Improved Free Flight IATA flight types (following v7 core changes, added more types)

27.MAY.24

* Improved module view path registering code
* Fixed CRON based FreeFlight hiding code to allow flexible usage of flight types.  
  _Changes require latest dev build as of 24th May 2024 or newer_

31.MAR.24

* Helper changes to match phpvms v7 improvements  
  _Change require latest dev build as of 28th March 2024_

22.MAR.24

* Fixed a possible issue in free flight blade (currencies with comma as seperator)
* Added a new category to Market (Tour Token)
* Updated Tour logic to allow defining tokens for tour, and not allowing pilots to participate tours when token/access is not bought (optional)

13.MAR.24

* Updated CronServices to cover a logical flaw in keeping tour flights hidden

22.FEB.24

* Provides an interim solution for Market item prices (for two currencies using `,` as decimal seperator)

11.FEB.24

* Removed `laravelcollective/html` package features/usage
  **WARNING: Code Breaking Changes Present, update your duplicated blades**

04.FEB.24

* Market prices now allow two decimal digits for precision
* Gifting through market frontend will only show active and on leave users
* Added manual backup feature (should be used in cases where cron fails or an urgent backup is needed)
* Added hourly local database backups via cron (disabled by default)
* Slightly altered module admin page

25.JAN.24

* Added API endpoints for Tours and Monthly Assignments
* Improved non-flown member deletion feature
* Improved database cleanup features

05.JAN.24

* Fixed Cron Services (regarding automated deletion of paused pireps)
* License update (Two new disallowed VA's are added)

31.DEC.23

* Added auto cancel/delete feature for paused pireps (needs working cron)

18.NOV.23

* Added bid checks for Free Flight aircraft suitability
* Updated tour leg check helper (to properly identify legs flown on one specific date)
* Updated license (Another disallowed VA was added)

07.OCT.23

* Market item owners display for admins
* Tour fleet management improvements, multiple tour/subfleet selections (Thanks to @arthurpar06 for his support)

17.SEP.23

* Removed `public_asset()` function from Market index as it was causing trouble with image url's
* License update (Another disallowed VA was added)  

03.SEP.23

* Added FeaturedItem and UserItems widgets for Market
* Improved market page (now allows category selection and sorting)
* Fixed some market related translations (Thanks to valuable friends from phpVMS Discord)  

02.SEP.23

* Added Market feature

20.AUG.23

* Added a cron setting for keeping Tour Flights invisible (so they will not show up on flight search)
* Added financial features to FreeFlights, for checking required balance and charging user.  

05.AUG.23

* Compatibility update for core v7 changes (Softdelete support and PirepState changes)

23.JUN.23

* Updated models to be compatible with Laravel10
* Fixed a bug with Ground Handling expenses (new fare logic compatibility)

16.JUN.23

**WARNING: THIS IS THE LAST VERSION SUPPORTING PHP 8.0.x AND LARAVEL 9**

* Updated custom income/expense listeners to follow new fare logic of v7 core
* Updated auto fare price calculator to work with new and copied fares

11.JUN.23

* Rounded up version, added compatibility notice

06.JUN.23

* Updated FreeFlight page (Thanks to @Tayrael for his support)  
  There was an error related to SimBrief callsign setting, when enabled it was blocking the dropdowns to act as designed

04.JUN.23

* Updated Free Flights (Now it is mandatory to select an aircraft to proceed on planning)
  There is a new setting which forces pilots to use matching aircraft according to their airline selections  
  When enabled; it will not be possible to select airline A and then selecting an aircraft of airline B for free flights

26.MAR.23

* Updated Free Flights (Core compatibility and Load Factor adjustment according to selected flight type)

12.JAN.23

* DE Translation (Thanks to [Cyber Air](https://www.cyber-air.org/))
* Tour legs auto activation/de-activation fix and improvements

17.DEC.22

* ES-ES Translation (Thanks to @arv187)
* License Update (Removal of access to updates and no support conditions explained briefly)

15.NOV.22

* PT-BR Translation (Thanks to @Joaolzc)
* PT-PT Translation (Thanks to @PVPVA , specially JCB)

13.NOV.22

* License Updated (more non-authorized virtual airlines added !)
* Added country flags to Assignments page (it will follow theme "*flights_flags*" setting)
* Added Map Widget support for Assignments page (*needs Disposable Basic with 13NOV Update or newer*)

23.OCT.22

* Added a failsafe for Airport Expenses (rare case, flight time 0 mins)
* Updated Tour Progress Widget (to support closed then re-opened tours with new dates/flights)

08.JUN.22

* Updated Free Flights logic (if SimBrief is not enabled redirect to Bids page)
* Added a failsafe to Open Tours Award class (Airline check)

04.JUN.22

* Added a new setting for Monthly Assignments (Use Pilot Company)

06.APR.22

* Added role/ability support for module backend features
* Improved Tour Reports
* Improved Tour Maps
* Improved Italian translation (Thanks to @Fabietto996)
* Improved Cron Database Cleanup Feature

14.MAR.22

* Module is now only compatible with php8 and laravel9
* All module blades changed to provide better support for mobile devices
* Module helpers updated to meet new core requirements
* Module controller and services updated to meet new core requirements
* Some more failsafe checks added to cover admin/user errors
* Tour Progress widget now checks flown legs order along with progress ratio
* Added Tour Award Winners (first 10 pilots)
* Improved Tour Reports performance

01.MAR.22

**WARNING: THIS IS THE LAST VERSION SUPPORTING PHP 7.4.xx AND LARAVEL 8**

* Failsafe (yes another one) for Tour Progress Widget
  *To cover division by zero error, happens if admin decides to delete all legs of an active tour after they have been flown*

28.FEB.22

* Refactored Tour Award classes for better performance (execute deeper checks when needed)
* Fixed Tour Award classes (was causing an error when a non-defined or non-active tour is being checked)
* Re-enabled tour leg order checks
* Added Tour Reports (visible to admins only, still Work In Progress)

19.FEB.22

* Added a failsafe for Flight Assignments widget (for deleted flights)
* Re-organized module admin page

14.FEB.22

* Updated and added new cron based features (Requires phpVms 7.0.0-dev+220211.78fd83 or later)
* Added a setting to enable/disable Web based Free Flights

11.FEB.22

* Fixed Divert notification, fixing and auto handling

05.FEB.22

* Added French translation (Thanks to Jbaltazar67, from phpVMS Forum)
* Fixed Maintenance Expenses applied to pilot more than once (when a re-calculation is done)
* Added "New User Registered" Discord notification, uses admin only channel (separate from core messages)
* Updated Monthly Assignments Widget (for proper ordering of assignments)
* Added a failsafe for Tour checks regarding deleted aircraft
* Added a failsafe for Maintenance checks regarding deleted aircraft

14.JAN.22

* Tour system performance improvements (Reduced load times and resource usage)

05.JAN.22

* Fixed a bug at NOTAM controller effecting proper display of effective NOTAMs

31.DEC.21

* Fixed the missing failsafe of Monthly Flight Assignments
* Fixed the typo error braking maintenance limits defined at Disposable Basic ICAO level

05.DEC.21

* Added "Type Ratings" support to Free Flight and Monthly Flight Assignments features

04.DEC.21

* Fixed possible migration errors when a custom table prefix is defined during phpvms install
* Improved admin handy functions (to fix possible problematic "active" SimBrief packages)  
  (Problematic = Has flight and pirep attachements but does not belong to an active flight/pirep, only blocking aircraft)

02.DEC.21

* Modified admin handy functions (Return Aircraft to Hub, requires phpVms 7.0.0-dev+211130.c45d52 or later)

26.NOV.21

* Added positive (or zero) landing rate failsafe for pirep based maintenance checks

22.NOV.21

* Fixed actual TOW and LW field checks (Airport Expenses)
* Added Italian translation (Thanks to Fabietto for his support)

20.NOV.21

* Tour Award classes fixed (equality type check)
* Refactored auto comments (not active, still on test)

18.NOV.21

* Some refactoring at event listeners (dynamic income/expense/maintenance to gain performance)
* Fixed a bug at FreeFlights (now both rank and airline restrictions do apply)
* Still a lot to do (specially for admin side Tour and Assignment reports and some cron automation for bigger Maintenance checks)

17.NOV.21

* Maintenance fixes (added missing index page, fixed table and settings)

16.NOV.21

* Initial Release
