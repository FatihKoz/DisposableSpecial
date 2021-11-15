# TurkSim Module

**PRIVATE** module, compatible with any latest dev build of phpVMS v7 released at or after **30.SEP.2021**. Provides;

* Tours (with Awards and a tracking Widget)
* Free Flights (with full SimBrief integration)
* Maintenance System (can be extended by Disposable Tech module)
* Monthy Flight Assignments
* NOTAMs
* Configurable per flight dynamic expenses (Catering, Parking, Landing, Terminal Services Fees etc)
* Configurable per flight dynamic income (Duty Free and Cabin Bouffet Sales)
* Roster page listing all pilots with their current states
* Some static pages like About Us, Rules & Regulations, Ops Manual, Live WX Map etc.
* Handy administrative functions

Module does not provide automatic links to your navbar (except admin section), so after enabling the module you need to check the pages and if they are working properly this means that the install process is completed and you can add the links to your navbar as you wish (examples are in usage section below)

## Important Info

* As this is a **PRIVATE** module, please do **NOT** share it with someone else without my knowledge.
* Some other developers do charge nice amounts for capabilities like Tours, I do not and module is free of charge.
* But this does not mean that you can simply share it with others just because it is free.
* This module is only available to some selected human beings for some special reasons
* Yes! You are one of those special human beings, and please do not make me regret my own choice :)

## Installation Steps

* Manual Install : Upload contents of the package to your root/modules folder via ftp or your control panel's file manager
* GitHub Clone : Clone/pull repository to your root/modules/TurkSim folder
* PhpVms Module Installer : Go to admin -> addons/modules , click Add New , select downloaded file and click Add Module

Go to admin section and enable the module, that's all.  
After enabling/disabling modules an app cache cleaning process may be necessary (check admin/maintenance).

* Check `Support Files` folder for some examples blades, simple acars ops manual and new country flag images.

## Usage

For example to add a link for Tours page to your navbar, you can use this example code;

```html
<li>
  <a class="nav-link" href="{{ route('TurkSim.tours') }}">
    <i class="fas fa-map-signs"></i>
    <span>Tours</span>
  </a>
</li>
```

Best way to add links in Laravel structure is to use routes like `<a href="{{ route('TurkSim.freeflight) }}">My Flight</a>`  
By using defined routes with above example you can change/update the routes and all will be compatible with updated Service Provider.

Static page routes are below;

```md
{{ route('TurkSim.aboutus') }}
{{ route('TurkSim.acarsmanual') }}
{{ route('TurkSim.landingrates') }}
{{ route('TurkSim.rulesandregs') }}
{{ route('TurkSim.wxmap') }} *Uses Windy as the iframe source, you can edit the blade as you wish*
```

And these are the dynamic (database driven) routes;

```md
{{ route('TurkSim.allpilots') }}
{{ route('TurkSim.assignments') }}
{{ route('TurkSim.freeflight') }}
{{ route('TurkSim.notams') }}
{{ route('TurkSim.tours') }}
{{ route('TurkSim.tour') }} *Must be used with a tour code!*
```

### How to define Tours ?

First you need to define your tour from *Admin > TurkSim Module*, then you need to add each tour leg from *PhpVms Admin > Flights* interface.  
When inserting your flights, the tour's legs in particular you need to use the tour code you defined as the route code and you need to define the legs in order. This is a little bit common knowledge about the tours and I think you already know that very well.

We are able to use different validity dates for the tours and the legs. Tours must have start and end dates, but the flights (legs) do not have to but if you want the legs to be flown between particular dates then  you can define each legs validity while inserting (or editing) the legs.

Imagine you are defining a tour for Formula1 Season 2021, your tour should start before the first race and end before or just after the last race. Also you want your pilots to fly the tour legs according to the race schedule, they will carry teams, fans and maybe cargo from race to race ! Then you need to define each legs validity period too ;) This will be a really hard tour though but it will be fun to complete as the races progress.

This logic may be extended as you wish.

If you have a multiple airline setup, then you can setup your tours for your airlines too. Checks will done according to that.  
Also two Award Classes are provided, one for Open Tours (no airline defined) one for Airline Tours (with airline checks).

**Update To Tours :** We are now able to use rich html within our tour definitions, you can use the WYSIWYG editor at admin side to add images to tour definitions, or make their descriptions look more nicer with tables, bold/italic text etc.  

### How to use NOTAMs ?

Well, it is totaly up to you. They will be displayed close to real life NOTAM format, you can use them as News like or inform your pilots about procedures for a special airport etc. Just check module admin page for Notam management.

*Replacing Notam* is used to override a previous notam. When used C0018/21 NOTAM**R 0001** is added to Notam Ident. (**R 0001** means 0018 is replacing 0001)

**A** is used for Airport Notams, **C** is used for Company Notams. I just removed the Q code from the equation for my own mental health :)

### How can I use Widgets provided ?

Simple, just use standard Laravel call for widgets, currently two widgets are available **Tour Progress** and **Notams**

```php
@widget('TurkSim::Assignments')
@widget('TurkSim::Notams')
@widget('TurkSim::TourProgress')
```  

Assignments widget has one config options called `user` which can be used to display a specific user's progres instead of current user.

* `['user' => $user->id]`

Tour Progress widget has two config options called `user` and `warn`  

* `['user' => $user->id]` will force the widget to display a specific user's progress
* `['warn' => 30]` will change the progress bar color and display the remaning days to complete the tour according to Tour's end date (default is 14 days)

Notams can be configured to display users current location notams or specific notams for an airport or airline. Default count is 25, which can be changed too.

* `['count' => 50]` will pick latest 50 Notams
* `['user' => true]` will check and user's current location
* `['airport' => $airport->id]` will check only specified airport for Notams
* `['airline' => $airline->id]` will only check specified airline's company notams

User and Airport can not be used together due to nature of the selection (they are both airport based), rest can be combined

* `['count' => 20, 'airline' => $airline->id, 'airport' => $hub->id]` this combination will display 20 notams of selected airline for specified airport  

### Dynamic Expenses

I tried to make them as close to the real world tariffs as possible, for most of them the calculation formulas are real but in some cases I had to use some non-realistic values 'cause we do not have them in our sim world :( For example when you land an airport, the landing fee is calculated with your aircraft's MTOW but we may not have defined a Maximum TakeOff Weight for it yet. In this case your actual Landing Weight is used. Or in a case where the great circle distance can not be calculated, the actual flight distance or worst the flight time is being used for Air Traffic Services fee calculation.

Settings are simple, if it is not just an enable/disable checkbox then you need to select the value you want to use for that calculation, here are their meanings;

* cap : Capacity (Aircraft total capacity)
* load : Actual Passenger (or Cargo) load
* lw : Actual Landing Weight
* tow : Actual Take Off Weight
* mtow : Maximum Take Off Weight

If you want to go realistic, chose cap and mtow as the authorities do. Always the bigger one, if you want more dynamic and flexible values you can chose cap or lw/tow etc.

Base values are ok for Euro and USD (since they are pretty close to each other, there will be no surprises about the generated monetary amounts). But if you are using something different as your phpVMS currency, I kindly suggest adjusting all base prices according to your needs. "Unit Rate" is the monetary amount being used in the calculations, imagine it like the per pax catering price including hot meal, soft drinks etc. Or the amount being used while doing calculations with the weights etc.

### Dynamic Income

Currently only Duty Free and Cabin Bouffet Sales are possible, they can be configured for flight types like int (international) or dom (domestic). Also item prices and airline profit percentage can be defined. Rest is pretty much automatic and dynamic, for each passenger flight module will determine a random number of customers and they will buy randomly picked items. So if you are lucky enough, you may gain a nice profit from in flight sales.

This feature needs dev build *210930.9a28cf* (dated 30.SEP.21) at minimum. Any later dev build will be ok too.

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

Technically all blade files should work with your template but they are mainly designed for Bootstrap compatible themes. So if something looks weird in your template then you need to edit them. I kindly suggest copying them under your theme folder and do your changes there, directly editing module files will only make updating harder for you.

All Disposable Modules are capable of displaying customized files located under your theme folders;

* Original Location : `root/modules/DisposableModule/Resources/Views/somefile.blade.php`
* Target Location   : `root/resources/views/layouts/YourTheme/modules/DisposableModule/somefile.blade.php`

## Update Notes

v2.2.1
* Logical improvements and bug fix for monthly assignments (to-do: admin side reports etc)
* Bug fix for Soft Landing check and related expense

v2.2.0
* Performance improvements and refactoring (no visual changes or loss of functions)
* Maintenance system bug fix (to-do: auto a/b/c checks)
* Improvements for assignments (to-do: admin side reports etc)

v2.1.9
* Fixed another logical bug in Monthly Flight Assignments
* Added two buttons for admins to re-assign flights for current month (temporary solution until the admin side gets completed)
* Improved the Flight Assignments widget visuals, added counts and link to Assignments page

v2.1.8
* Refactored Tour Progress Widget
* Fixed a logical bug in Monthly Flight Assignments

v2.1.7
* Added Monthly Flight Assignments

v2.1.6
* Added dynamic expenses (needs *dev.210930.9a28cf* build or later)

v2.1.5
* Added Tour specific rules (like tour descriptions, rules can be defined with rich text and images etc)
* Improved Tour Map code (moved all blade side logical operations to controller)
* Improved module helpers

v2.1.4
* Added Notams (both as a new page and as a Widget).
* Added Maintenance Operations.
* Maintenance Systems works with a close relation to Disposable Tech module, please update your DispoTech module to latest version too.
* Added a listener for Hourly Cron event of phpVMS. Since I am using my own crons and not able to test cron, hoping that it will work without errors with core cron logic.
* For this update, please follow below order to update if you are doing the updates one by one.

`Disposable Tech > TurkSim > Disposable Airlines` (or update all the same time)

* Also slightly edited tours page visual and tour details page, changed the admin side for tours.

v2.1.3
* Refactored module settings system
* Moved almost all settings of expenses to database and tied them to settings
* Fixed Free Flight logic (it will not be possible to save the flight with unknown & not found airports)
* Improved module helpers and fixed non working tour leg check
* Added an example dashboard evaluator usage blade
* Fixed migration (the order of module updates is not a problem now)

v2.1.1
* Added capability of rewarding Random Flights, by default it is disabled. Check module admin page ;)
* Cleaned up some code

v2.1.0
* Updated Tour Progress widget
* Cleaned up some helpers and removed some (DisposableTools module is almost mandatory for TurkSim module)
* Release package structure change to match PhpVms Module installer requirements

v2.0.6
* Improved fuel expense listeners to provide better solutions, specially when de-fuelling and/or tankering is done
* Improved diversion listener to match latest changes done to vmsacars and phpvms core
* Added German Translation (thanks @GAE074)

v2.0.5
* Improved Helper to use the latest accepted pirep while checking flown status (pilots may send the same pirep twice due to error).
* Also improved the TourProgress Widget to count only one pirep for each leg if there are more than one reported.

v2.0.4
* Free Flight option now checks for the aircraft availability according to latest phpvms core improvements.
* Also added a new Discord Msg option for Diversions. When a diversion is detected by the module, a Discord Msg will be dispatched to your server, prefably to a admin only channel. It will also inform you about the possible reason for the diversion. It is not an exact reason, but a possible/probable cause.

v2.0.0
* Module is now capable of listening Pirep events and provides some dynamic expenses like (Fuel Service, De-Fuelling, ATC Services etc).
* Also some dynamic income would be provided (like DutyFree sales for international flights).
* If you need help about editing them just let me know. I will move their settings to a database table soon, for now only direct edits to listeners is possible.

Note; Having dynamic income needs some core phpvms changes, I am using them but at the mean time waiting Nabeel to provide the official change for this subject. Expenses do work without any changes to core.

v1.3
* Due to separating part of TurkSim Module as Disposable Modules, most of the files are changed and/or removed from TurkSim folder.So please check the package you downloaded and remove files from your phpvms installation too.
* If you do NOT have any edited blades and/or controllers under TurkSim folder, you can simply delete it and upload the contents of this package.
* Also if you have any edited blades, you can move them to a safe place and then check the differences to apply them back.
* All removed parts are now separate modules with more functions and improvements.
* New version of Disposable Theme will be fully compatible with separated Disposable Modules.
* Sorry for the trouble, but we needed this as you all know and the better the earlier this is done :)

Currently TurkSim Module holds our static pages, some handy functions, All Pilots, Tours and Free Flight functionality. Rest is all gone (for good)  

v1.2
* Please delete all old version and related files of the module before updating. Files to delete;

```md
* root\modules\Awards\Awards                            : 3 files, names starting with TurkSim
* root\modules\TurkSim                                  : delete everything, just backup blade/view files if you have edits
* root\app\Widgets                                      : TourProgress.php
* root\resources\views\layouts\default\widgets\         : tourprogress.blade.php
* root\resources\views\layouts\YourThemeFolder\widgets\ : tourprogress.blade.php
```

* If you have edited version of the blade files, please save them to a secure place. With ver 1.2 you are able to use your own version of blade/view files. Just create two new folders under your theme folder like this "modules\TurkSim", then copy blade files you already edited and/or want to edit to that folder. For more details and example you can check module admin page.
