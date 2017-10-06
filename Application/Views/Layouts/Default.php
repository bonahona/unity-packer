<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width">
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
        <meta name="description" content="Application to take a Git repo and package it as a Unity package"/>
        <meta name="author" content="Björn Fyrvall"/>
        <?php echo $this->Html->Favicon('fyrvall-favicon.png');?>
        <title><?php echo $title;?></title>
    </head>
    <body>
        <div class="container-fluid">
            <?php echo $view;?>
        </div>
    </body>
</html>