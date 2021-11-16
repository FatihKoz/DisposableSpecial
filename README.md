# Disposable Extended Pack v3

phpVMS v7 module for Extended VA features

:warning: This is a **PRIVATE** module, do **NOT** redistribute without author's written approval :warning:

Compatible with any latest development (dev) build of phpVMS v7 released after **09.NOV.21**.  
Using this module along with *Disposable Basic* and *Disposable Theme* is highly advised but not mandatory.  
Module blades are designed for themes using **Bootstrap v5.x** and FontAwesome v5.x (not v6) icons.

This module pack aims to cover extended needs of any Virtual Airline with some new pages, widgets and backend tools. Provides;

* Tours (with Awards and a tracking Widget)
* Free Flights (with full SimBrief integration)
* Maintenance System (can be extended by Disposable Basic module)
* Monthy Flight Assignments
* NOTAMs
* Configurable per flight dynamic expenses (Catering, Parking, Landing, Terminal Services Fees etc)
* Configurable per flight dynamic income (Duty Free and Cabin Bouffet Sales)
* Some static pages (About Us, Rules & Regulations, Ops Manual, Landing Rates)
* Handy administrative functions

## Important info about License Conditions

* Please do read the License, it is really short but holds important information.
* This is a **PRIVATE** module, do **NOT** share it with someone else without my written approval.
* Some other developers do charge nice amounts for single capabilities like only Tours, I do not.
* Module is technically **"DonationWare"**, where you donate (for good) and how much you donate is up to you.

### DonationWare Explained

Yes, the module is not FREE but it does not have fixed price tag too. So you will decide how much you donate and where you donate. Below you will see some options, and yes I am at the very last line of that list.

* Religious Community (nearby church or mosque etc, people you really know helping others in need for the glory of God)
* Neighborhood community (like the one above, helping homeless and poor)
* Military staff community (like helping wounded soldiers of your country, those men risked their lives for us amd deserve some support from us too)
* Animal shelters nearby (yes, their lives are important too and much more important than most of crowd around us)
* Author of this module (if you really want to and can not find somewhere else for donation)

How you can donate to me ? As this is the last choice, there are no pre-defined ways. You may gift something from simulation software shops (like gift cards etc) or just send me an email about it so we can find a way. You can follow me on Patreon for continuous monthly support (I really do not know how it works or even if I can get the money from them).

And just a friendly reminder, by doing a donation you will not own the code or will have priority support etc. As long as I am around, I will keep updating the module 'cause development never ends. But this does not mean that I will add everything to this module or when you request it :) If your request is reasonable and not va/company specific I may work on it. If not you may need to come up with our own solution, prefably as a separate module for yourself (so you can easily update this module when needed)

## Installation and Updates

* Manual Install : Upload contents of the package to your phpvms root `/modules` folder via ftp or your control panel's file manager
* GitHub Clone : Clone/pull repository to your phpvms root `/modules/DisposableSpecial` folder
* PhpVms Module Installer : Go to admin -> addons/modules , click Add New , select downloaded file then click Add Module

* Go to admin > addons/modules enable the module
* Go to admin > dashboard (or /update) to trigger module migrations
* When migration is completed, go to admin > maintenance and clean `application` cache

:information_source: *There is a known bug in v7 core, which causes an error/exception when enabling/disabling modules manually. If you see a server error page or full stacktrace debug window when you enable a module just close that page and re-visit admin area in a different browser tab/window. You will see that the module is enabled and active, to be sure just clean your `application` cache*

### Update (from v3.xx to v3.yy)

Just follow the installation procedure by overwriting your old module files

### Update (from v2.xx series to v3.xx)

Below order and steps are really important for proper update from old modules to new combined module pack

:warning: **There is no easy going back to v2 series once v3 is installed !!!** :warning:  
**Backup your database tables and old module files before this process**  
**Only database tables starting with `turksim_` is needed to be backed up**

* From admin > addons/modules **DISABLE** all old TurkSim modules
* From admin > addons/modules **DELETE** all old TurkSim modules
* Go to admin > maintenance and clean `all` cache
* Install Disposable Special module (by following installation procedure)

## Module links and routes

Module does not provide auto links to your phpvms theme, Disposable Theme v3 has some built in menu items but in case you need to manually adjust or use a different theme/menu, below are the routes and their respective url's module provide

Named Routes and Url's

```php
DSpecial.tours          /dtours            // Tours index page
DSpecial.tour           /dtours/WT21       // Tour details page, needs a tour {code} to run

DSpecial.assignments    /dassignments      // Monthly Assignments index page
DSpecial.freeflight     /dfreeflight       // (Personal) Free Flight index page
DSpecial.maintenance    /dmaintenance      // Fleet Maintenance index page
DSpecial.notams         /dnotams           // Notams index page

DSpecial.ops_manual     /dopsmanual        // Operations Manual page (partly db driven, mostly static)
DSpecial.landing_rates  /dlandingrates     // Landing Rates page (Static content)
DSpecial.about_us       /daboutus          // About US page (Static content, public)
DSpecial.rules_reg      /drulesandregs     // Rules and Regulations page (Static content, public)
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

## Operational Usage and Provided Features

Below you can find some details about how this module is designed and how it behaves according to your configuration options.

### How to define Tours ?

First you need to define your tour from *Admin > Disposable Special*, then you need to add each tour leg from *PhpVms Admin > Flights* interface.  
When inserting your flights, the tour's legs in particular you need to use the tour code you defined as the route code and you need to define the legs in order. This is a little bit common knowledge about the tours and I think you already know that very well.

We are able to use different validity dates for the tours and the legs. Tours must have start and end dates, but the flights (legs) do not have to but if you want the legs to be flown between particular dates then  you can define each legs validity while inserting (or editing) the legs.

Imagine you are defining a tour for Formula1 Season 2021, your tour should start before the first race and end before or just after the last race. Also you want your pilots to fly the tour legs according to the race schedule, they will carry teams, fans and maybe cargo from race to race ! Then you need to define each legs validity period too ;) This will be a really hard tour though but it will be fun to complete as the races progress.

This logic may be extended as you wish.

If you have a multiple airline setup, then you can setup your tours for your airlines too. Checks will done according to that.  
Also two Award Classes are provided, one for Open/Generic Tours (no airline defined) one for Airline Tours (with airline checks).

### How to use NOTAMs ?

Well, it is totaly up to you. They will be displayed close to real life NOTAM format, you can use them as News like or inform your pilots about procedures for a special airport etc. Just check module admin page for Notam management.

*Replacing Notam* is used to override a previous notam. When used C0018/21 NOTAM**R 0001** is added to Notam Ident. (**R 0001** means 0018 is replacing 0001)

**A** is used for Airport Notams, **C** is used for Company Notams. I just removed the Q code from the equation for my own mental health :)

### How can I use Widgets provided ?

Simple, just use standard Laravel call for widgets, currently 3 widgets are available **Assignments**, **Notams** and **Tour Progress**

```php
@widget('DSpecial::Assignments')
@widget('DSpecial::Notams')
@widget('DSpecial::TourProgress')
```  

**Assignments** widget has one config option called `'user'` which can be used to display a specific user's progres instead of current user.

* `'user'` can be a user's id (like `$user->id` or `4`)

**Tour Progress** widget has two config options called `'user'` and `'warn'`  

* `'user'` can be a user's id (lie `$user->id` or `3`) and will force the widget to display that user's progress
* `'warn'` can be any number of days like `30` and it will change the progress bar color according to Tour's end date (default is 14 days)

**Notams** widget can be configured to display users current location notams or specific notams for an airport or airline.

* `'count'` can be any number (like `50`) to pick latest *EFFECTIVE* specified number of notams
* `'user'` can be either `true` or `false` to check user's current location (default is `false`)
* `'airport'` can be an airport id (like `$airport->id` or `BIKF`) to check only specified airport notams
* `'airline'` can be an airline id (like `$airline->id` or `18`) to check specified airline's company notams

User and airport options can not be used together due to nature of the selection (they are both airport based), rest can be combined

* `['count' => 20, 'airline' => $airline->id, 'airport' => $hub->id]` this combination will display 20 notams of selected airline for specified airport

Widget will **always** display **effective** notams, config options can not change this behavior.

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

Also there are some flight hour and cycle definitions for main checks, you can use them for all your fleet or you can go crazy and define realistic figures for each ICAO Type you have. Like an extended period for C172 but a realistic period for B738 etc. It is up to you, if you are using one or two similar types, then using the main settings would be practical but for a larger fleet, using Disposable Tech and defining type specific periods would be much realistic.

When you first install the update (or the module first time), your pireps will be read and a starting point for maintenance will be created. From that moment on, every accepted pirep will increase/decrease some values. Like a hard landing hits kindly on the current state of the aircraft (according to the landing rate of course) but a nice landing will make barely noticable impact. Below %75, you will be offered to perform a *Line Check* manually, also (for the time this readme is updated) A/B/C Checks are being started manually when needed. I need to find a sweet spot for them to be started automatically by the cron, right now I do not want to push something hurting your server performance.

Since everything is dynamic, maintenance costs are dynamic too. Default base price offered is ok for KG and USD/EURO region. If you are using LBS or using another currency it may not fit your expectations well. Technically the heavier the aircraft (MTOW or last TakeOff Weight if you do not define MTOW at aircraft level), the expensive the maintenance becomes, also the hardness of the landing or the tailstrike effects the price. Imagine same ICAO Type or same aircraft landing with -513 ft/min and then with -627 ft/min, prices will not be the same.

Even though vmsAcars is not reporting TakeOff pitch and roll, module is able to check them too. Currently only landing phase checks will be working.

Due to the current state of phpVMS v7, minimum cron execution time is one hours. So setting generic check duration to *0.50* hours (results *30 mins*) will not make your aircraft released after 30 minutes 'cause the cron runs hourly and it will be checked/released on the next hour. IF you have custom crons like Quarterly (15 mins interval) or Halfly (running every 30 mins) then setting values below 1 hour would be practical. Or you can make 1.5 hours etc as you wish.

### Monthly Flight Assignments

This system relies heavily on your flight structure and database records. The settings are pretty basic, it also considers your phpVMS settings too. Auto assignments requires cron to be running, if somehow it fails or you wish to manually trigger the process it is possible to do so.

Most critical part is assigning subfleets to flights, if you have a flexible/relaxed setup where flights have no subfleet definitions then `Use Prefered ICAO Types` setting will not work at all and most probably it will disable itself during assignment process. Likewise phpVMS setting `Restrict Aircraft to Ranks` will have no effect for assignments in this setup.

The worst scenario is, having some leftover data in some database tables and also trying to have a mixed setup (like %50 of the flights have subfleets, rest free etc). In this scenario some users may have assignments, some not! Will try to find a way to overcome this without reducing capabilities and keeping the performance level same.

Also if you plan to use `Average Flight Times` option, then setting a logical margin is important. Setting a margin of for example 120 mins (2 hours) will work of course but it will simply disable the logic behind using avg flight times of a pilot. Imagine a user, with an avg flight time of 2 hours, this means that personally he/she is not prefering to fly longer flights. With a margin of 120 minutes, you will be kindly forcing that user to have an assigment flight with for example 3 hours and 50 minutes! Or maybe a quick hop with 30 minutes only. I personally prefer having the margin set to maximum 60 minutes, best is 30 minutes in my opinion. If a flight is not found within user's flight time range (avg +/- margin) then code doubles the margin and re-checks (avg +/- 2x margin).

If you have multiple airlines in your setup, code tries to use the same airline between city pairs and only attempts to change the airline in hubs.

## Duplicating Module Blades/Views

Technically all blade files should work with your template but they are mainly designed for Bootstrap v5.* compatible themes.  
So if something looks weird in your template then you need to edit the blades files.  
I kindly suggest copying/duplicating them under your theme folder and do your changes there, directly editing module files will only make updating harder for you.

All Disposable Modules are capable of displaying customized files located under your theme folders;

* Original Location : phpvms root `/modules/DisposableSpecial/Resources/views/tours/some_file.blade.php`
* Target Location   : phpvms root `/resources/views/layouts/YourTheme/modules/DisposableSpecial/tours/some_file.blade.php`

As you can see from the above example, filename and sub-folder location is not changed. We only copy a file from a location to another and have a copied version of it.  
If you have duplicated blades and encounter problems after updating the module or after editing, just rename them to see if the updated/provided original works fine.

## Release / Update Notes

* 15.NOV.21

Initial release