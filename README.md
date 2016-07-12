# CodelessUi - a standards compliant, DOM-based PHP template engine.

CodelessUi is a PHP template engine for HTML. It facilitates the separation of presentation code (HTML) from application code (PHP). It helps you associate contents from inside your application to individual elements in a HTML template. You can even do more; it is actually built for anything template.

Note one difference from other template engines like Twig and Smarty: CodelessUi is not syntax-based; it is DOM-based. This means that there is no syntax to learn - not even one!

## Consider an example - and compare

### Samrty - (from smarty.net):

#### The php

  include('Smarty.class.php');

----------------

  // create object

  $smarty = new Smarty;

----------------

  // assign some content. This would typically come from

  // a database or other source, but we'll use static

  // values for the purpose of this example.

  $smarty->assign('name', 'george smith');

  $smarty->assign('address', '45th & Harris');

----------------

  // display it
  $smarty->display('index.tpl');

----------------
  
#### The template - before

  \<html\>

  \<head\>

  \<title\>Info\</title\>

  \</head\>

  \<body\>


  \<pre\>

  User Information:


  Name: {$name}

  Address: {$address}

  \</pre\>


  \</body\>

  \</html\>

#### The template - after


  \<html\>

  \<head\>

  \<title\>Info\</title\>

  \</head\>

  \<body\>


  \<pre\>

  User Information:


  Name: george smith

  Address: 45th & Harris

  \</pre\>


  \</body\>

  \</html\>


### CodelessUi:

#### The php

  include('lib/CodelessUi.php');

----------------

  // create object

  $CodelessUi = new CodelessUi;

----------------

  // assign some content. This would typically come from

  // a database or other source, but we'll use static

  // values for the purpose of this example.

  $CodelessUi->assignData('#name::after', 'george smith');

  $CodelessUi->assignData('#address::after', '45th & Harris');

----------------

  // display it

  $CodelessUi->setTemplate('index.html');

  $CodelessUi->render();

  
#### The template - before

  \<html\>

  \<head\>

  \<title\>Info\</title\>

  \</head\>

  \<body\>


  \<pre\>

  User Information:

  \<span id="name"\>Name: \</span\>

  \<span id="address"\>Address: \</span\>

  \</pre\>


  \</body\>

  \</html\>

#### The template - after

  \<html\>

  \<head\>

  \<title\>Info\</title\>

  \</head\>

  \<body\>


  \<pre\>

  User Information:

  \<span id="name"\>Name: george smith\</span\>

  \<span id="address"\>Address: 45th & Harris\</span\>

  \</pre\>

  \</body\>

  \</html\>

----------------
  
## The similarities
  * Instantiating with the 'new' keyword - same.
  * Assigning data to named elements in the markup - Smarty: assign(); CodelessUi: assignData().
  * Displaying - Smarty: display(); CodelessUi: render().

## The differences - lest you think they're the same all the way:

----------------
Smarty

  * A smarty template is not a standard HTML markup. But a mix of HMTL and Smarty's own tags and syntaxes.
  * A Smarty template file has the file extension .tpl. Not .html
  * You must learn both PHP, HTML and Smarty syntaxes to work with smarty.

----------------
CodelessUi

  * Use any valid HTML markup is a template! Valid means a markup without foreign syntaxes {}, {{}}, and all the rest! It means: Codeless Markup! Just markup!
  * Template file extension is rightly .html
  * You've learned PHP and HTML already! And that's all! That's the standard.
  Furthermore, if you know CSS (HTML's sister), you can even target UI elements by ID ($CodelessUi->assignData('#element', '...')), ClassName ($CodelessUi->assignData('.element', '...')), Attribute ($CodelessUi->assignData('element[attr]', '...')).
  And if you're a pro, find anything on the UI with xpath query: $CodelessUi->assignData('xpath://parent/child', '...').

You should by now see the possibilities! See the official docs, and tutorials! 

# Installation
## Requirement
  CodelessUi requires a web server running PHP 5.3 or greater.
## Installation
  Download CodelessUi if you have not already done so.
### Folder Structure
Extract the CodelessUi zip file and you’ll discover the most important folder for use named ‘lib’.

This folder contains three .php files: CodelessUi.php, CodelessUiDom.php and CodelessUiNodeList.php. *This folder and its content are things you SHOULD NOT edit*.

Move the CodelessUi folder to the frontend directory of your project or anywhere from the root directory of your project – depending on your application’s directory structure. Just make sure your application’s autoloader can pick up the CodelessUiMachine class when called – that’s if your project is bundled with an autoloader. Or simply note down the path to where you decide to put the CodelessUi files so you can manually include this path during setup.

# Test
If you just want to test CodelessUi or if your project is nothing more than basic, here is a test-case setup in numbered steps.
* Create a new php file named ‘app.php’ – just the name for this example.
* Copy the CodelessUi folder to the same directory as the app.php file.
* Create a plain HTML page named ‘template.html’- one that contains no php tags – and put the file in this same directory.

Then in your app.php:

* Include the CodelessUi class.

  Include ‘CodelessUi/lib/CodelessUi.php’;

  If you stored the CodelessUi folder in a different location, your include path would change.

----------------

  Include ‘path-to-CodelessUi/CodelessUi/lib/CodelessUi.php’;

  // Where ‘path-to-CodelessUi’ is your actual path to where you stored CodelessUi
 
----------------

* CodelessUi is now available to our app.php script, so we instantiate it:

  $CodelessUi = new CodelessUi;

  // The CodelessUi’s __constructor accepts no arguments

----------------

* Now, we hand CodelessUi the template to use - our template.html page

  $CodelessUi->setTemplate(‘template.html’);

  If your stored template.html in a different location, your path would change.

----------------

  $CodelessUi->setTemplate(‘path-to-template/template.html’);

  // Where ‘path-to-template is your actual path to where you stored template.html


----------------

* Now we can start assigning content to the respective elements in the template using CodelessUi’sassignData() function

  The function accepts to parameters:


    - i	$element_selector string
    - ii	$data

----------------

  // For document title (title)

  $CodelessUi->assignData(‘title’, ‘This is document title’);

----------------

  // For page heading 1 (h1)

  $CodelessUi->assignData(‘h1’, ‘Hello World!’);

----------------

  // For page paragraph (p)

  $CodelessUi->assignData(‘p’, ‘Here is my first CodelessUi project’);

----------------

* Finally, we render our page using CodelessUi’s render() function

  $CodelessUi->render();

----------------

And that’s it! Preview your app.php in a browser and experience the CodelessUi's simplicity and neatness first time on your project!

# Follow Up
Visit https://www.twitter.com/CodelessUi.

And follow CodelessUi on http://www.facebook.com/CodelessUi

# Authors
  Oxford Harrison <ox_harris@yahoo.com>
  
## Acknowledgments

Thanks for being around:

Ademola Adekunbi

Ezra Bassey

Daniel Okorie

# License
See the LICENSE.md file for details
  
