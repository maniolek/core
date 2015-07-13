Vegas CMF Core - Generator
==============

Generator allows you to create:
- basic module structure with Module.php file.
- controllers, models and tasks.

To make your work comfortable, we prepared also task for this purpose.

#Generator usage

##Module generator
 ```php
$path = APP_ROOT . '/modules';
$generator = new Module('ModuleName');
$generator->setPath($path);
$generator->run();
 ```
 
 ##Controller generator
 Controller generator allows to create controller class. There's possibility to create CRUD Controller, by setting third argument on true. We provide functionality for adding actions to your controller. It can be made by using ```addAction($actionName)``` method.
 
 ###Controller class

 ```php
$path = APP_ROOT . '/modules';
$generator = new Controller('ModuleName', 'ControllerName');
$generator->setPath($path);
$generator->run();
 ```
 
 ###CRUD Controller

 ```php
$path = APP_ROOT . '/modules';
$generator = new Controller('ModuleName', 'ControllerName', true);
$generator->setPath($path);
$generator->run();
 ```
 
 ###Controller class with predefined actions.

 ```php
$path = APP_ROOT . '/modules';
$generator = new Controller('ModuleName', 'ControllerName');
$generator->addAction('firstExampleAction');
$generator->addAction('secondExampleAction');
$generator->setPath($path);
$generator->run();
 ```

 ##Model generator
 ```php
$path = APP_ROOT . '/modules';
$generator = new Model('ModuleName', 'ModelName');
$generator->setPath($path);
$generator->run();
 ```
 
 ##Task generator

 Task generator provides you functionality to create skeleton class of Task.

 ```php
$path = APP_ROOT . '/modules';
$taskGenerator = new Task('ModuleTest', 'taskName');
$taskGenerator->setPath($path);
$taskGenerator->run();
 ```

 Additionally, you can optionally set action name.

 ```php
$path = APP_ROOT . '/modules';
$taskGenerator = new Task('ModuleTest', 'taskName');
$taskGenerator->setPath($path);
$taskGenerator->addAction('defaultActionName');
$taskGenerator->run();
 ```

#CLI Tasks

##Module generator

```bash
php cli/cli.php vegas:module create -n FoobarModule
```

## Controller generator

Controller class based on ControllerAbstract

```bash
php cli/cli.php vegas:mvc controller -m FoobarModule -n ControllerName
```

Controller class based on ControllerAbstract with predefined actions

```bash
php cli/cli.php vegas:mvc controller -m FoobarModule -n ControllerName -a firstExample,secondExample
```

CRUD controller

```bash
php cli/cli.php vegas:mvc crud -m FoobarModule -n ControllerName
```

## Model generator

```bash
php cli/cli.php vegas:mvc model -m FoobarModule -n ModelName
```

## Task generator

```bash
php cli/cli.php vegas:generator task -m FoobarModule -n ModelName -a test1
```