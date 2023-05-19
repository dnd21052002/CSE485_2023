<?php
    require_once '../includes/database-connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<nav class="navbar fixed-top navbar-light bg-white navbar-expand shadow" aria-label="Site navigation">
    
    <button class="navbar-toggler aabtn d-block d-md-none px-1 my-1 border-0" data-toggler="drawers" data-action="toggle" data-target="theme_moove-drawers-primary" data-disabled-toggle="undefined" data-toggle="undefined" data-original-title="">
        <span class="navbar-toggler-icon"></span>
        <span class="sr-only">Side panel</span>
    </button>

    <a href="https://vietcodedi.com/my/" class="navbar-brand d-none d-md-flex align-items-center m-0 mr-4 p-0 aabtn">
            VietCodeDi
    </a>

        <div class="primary-navigation">
            <nav class="moremenu navigation observed">
                <ul id="moremenu-6466e7089c8bf-navbar-nav" role="menubar" class="nav more-nav navbar-nav">
                            <li data-key="home" class="nav-item" role="none" data-forceintomoremenu="false">
                                        <a role="menuitem" class="nav-link  " href="https://vietcodedi.com/?redirect=0" tabindex="0" aria-current="true">
                                            Home
                                        </a>
                            </li>
                            <li data-key="myhome" class="nav-item" role="none" data-forceintomoremenu="false">
                                        <a role="menuitem" class="nav-link  " href="https://vietcodedi.com/my/" tabindex="-1">
                                            Dashboard
                                        </a>
                            </li>
                            <li data-key="mycourses" class="nav-item" role="none" data-forceintomoremenu="false">
                                        <a role="menuitem" class="nav-link  " href="https://vietcodedi.com/my/courses.php" tabindex="-1">
                                            Các khóa học của tôi
                                        </a>
                            </li>
                    <li role="none" class="nav-item dropdown dropdownmoremenu d-none" data-region="morebutton">
                        <a class="dropdown-toggle nav-link " href="#" id="moremenu-dropdown-6466e7089c8bf" role="menuitem" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" tabindex="-1">
                            More
                        </a>
                        <ul class="dropdown-menu dropdown-menu-left" data-region="moredropdown" aria-labelledby="moremenu-dropdown-6466e7089c8bf" role="menu">
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>

    <ul class="navbar-nav d-none d-md-flex my-1 px-1">
        <!-- page_heading_menu -->
        
    </ul>

    <div id="usernavigation" class="navbar-nav ml-auto">
        <div class="navbarcallbacks">
            
        </div>
        <div class="popover-region collapsed popover-region-notifications" id="nav-notification-popover-container" data-userid="444" data-region="popover-region">
<div class="popover-region-toggle nav-link icon-no-margin" data-region="popover-region-toggle" role="button" aria-controls="popover-region-container-6466e7089f9286466e708971fa3" aria-haspopup="true" aria-label=" Show notification window with no new notifications   " tabindex="0">
            <i class="icon fa fa-bell-o fa-fw " title="Toggle notifications menu" role="img" aria-label="Toggle notifications menu"></i>
    <div class="count-container hidden" data-region="count-container" aria-hidden="true">
        0
    </div>

</div>
<div id="popover-region-container-6466e7089f9286466e708971fa3" class="popover-region-container" data-region="popover-region-container" aria-expanded="false" aria-hidden="true" aria-label="Notification window" role="region">
    <div class="popover-region-header-container">
        <h3 class="popover-region-header-text" data-region="popover-region-header-text">Notifications</h3>
        <div class="popover-region-header-actions" data-region="popover-region-header-actions">        <a class="mark-all-read-button" href="#" title="Mark all as read" data-action="mark-all-read" role="button" aria-label="Mark all as read">
        <span class="normal-icon"><i class="icon fa fa-check fa-fw " aria-hidden="true"></i></span>
        <span class="loading-icon icon-no-margin"><i class="icon fa fa-circle-o-notch fa-spin fa-fw " title="Loading" role="img" aria-label="Loading"></i></span>
    </a>
        <a href="https://vietcodedi.com/message/notificationpreferences.php" title="Notification preferences" aria-label="Notification preferences">
            <i class="icon fa fa-cog fa-fw " aria-hidden="true"></i></a>
</div>
    </div>
    <div class="popover-region-content-container" data-region="popover-region-content-container">
        <div class="popover-region-content" data-region="popover-region-content">
                    <div class="all-notifications" data-region="all-notifications" role="log" aria-busy="false" aria-atomic="false" aria-relevant="additions"></div>
    <div class="empty-message" tabindex="0" data-region="empty-message">You have no notifications</div>

        </div>
        <span class="loading-icon icon-no-margin"><i class="icon fa fa-circle-o-notch fa-spin fa-fw " title="Loading" role="img" aria-label="Loading"></i></span>
    </div>
            <a class="see-all-link" href="https://vietcodedi.com/message/output/popup/notifications.php">
                <div class="popover-region-footer-container">
                    <div class="popover-region-seeall-text">See all</div>
                </div>
            </a>
</div>
</div><div class="popover-region collapsed" data-region="popover-region-messages">
<a id="message-drawer-toggle-6466e708a056d6466e708971fa4" class="nav-link popover-region-toggle position-relative icon-no-margin" href="#" role="button">
    <i class="icon fa fa-comment-o fa-fw " title="Toggle messaging drawer" role="img" aria-label="Toggle messaging drawer"></i>
    <div class="count-container hidden" data-region="count-container">
        <span aria-hidden="true">0</span>
        <span class="sr-only">There are 0 unread conversations</span>
    </div>
</a>
<span class="sr-only sr-only-focusable" data-region="jumpto" tabindex="-1"></span></div>
        <div class="d-flex align-items-stretch usermenu-container" data-region="usermenu">
                <div class="usermenu">
                        <div class="dropdown show">
                            <a href="#" role="button" id="user-menu-toggle" data-toggle="dropdown" aria-label="User menu" aria-haspopup="true" aria-controls="user-action-menu" class="btn dropdown-toggle">
                                <span class="userbutton">
                                    <span class="avatars">
                                            <span class="avatar current">
                                                <span class="userinitials size-35">CN</span>
                                            </span>
                                    </span>
                                </span>
                            </a>
                            <div id="user-action-menu" class="dropdown-menu dropdown-menu-right">
                                <div id="usermenu-carousel" class="carousel slide" data-touch="false" data-interval="false" data-keyboard="false">
                                    <div class="carousel-inner">
                                        <div id="carousel-item-main" class="carousel-item active" role="menu" tabindex="-1" aria-label="User">
                                            <a id="accessibilitysettings-control" href="#" class="dropdown-item" role="menuitem" tabindex="-1" aria-label="Accessibility">
                                                Accessibility
                                            </a>
                
                                            <div class="dropdown-divider"></div>
                
                                                    <a href="https://vietcodedi.com/user/profile.php" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Profile
                                                    </a>
                                                
                                                    <a href="https://vietcodedi.com/grade/report/overview/index.php" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Grades
                                                    </a>
                                                
                                                    <a href="https://vietcodedi.com/calendar/view.php?view=month" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Calendar
                                                    </a>
                                                
                                                    <a href="https://vietcodedi.com/user/files.php" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Private files
                                                    </a>
                                                
                                                    <a href="https://vietcodedi.com/reportbuilder/index.php" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Reports
                                                    </a>
                                                
                                                <div class="dropdown-divider"></div>
                                                    <a href="https://vietcodedi.com/user/preferences.php" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Preferences
                                                    </a>
                                                <div class="dropdown-divider"></div>
                                                    <a href="https://vietcodedi.com/login/logout.php?sesskey=Jv6uVYcGsJ" class="dropdown-item" role="menuitem" tabindex="-1">
                                                            
                                                        Log out
                                                    </a>
                                                
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
        </div>
        
    </div>
</nav>
</body>
</html>