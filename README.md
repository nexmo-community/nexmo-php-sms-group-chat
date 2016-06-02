GroupChat Demo & Tutorial
=========================

This is a simple example of a group chat, using Nexmo and the official [php client library][php_lib]. You can clone and 
run, or check out the [tutorial and follow along][tutorial].

Installation
------------

Clone the repository ([or download the source][source]):

    $ git clone git@github.com:nexmo-community/nexmo-sms-group-chat.git

Once you have the code, install the dependencies (just the [php client library][php_lib]) using composer:

    $ composer install
    
Configuration
-------------

Edit [`config.dist.php`][config] with your MongoDB URL, and your Nexmo credentials then rename it `config.php`. 

If you don't have an exsisting MongoDB database, [mLab][mLab] has a free tier, and [compose.io][compose] has a free 
30 day trial. 

Of course a [Nexmo account][account] is needed as well, and you can [signup for free][free].

Running the Demo
----------------

You can setup a full web server (apache, nginx) and configure the web root as `/public`; however, the built in php 
development server is easy to use on your local machine: 

    $ php -S 0:8080 -t ./public 
    
Since Nexmo needs to be able to make a web request to deliver inbound messages, your server needs to either be on a 
public web server, or you need to expose your local server using something like [ngrok][ngrok]. 

    $ ngrok http 8080 --subdomain=something_unique

Once your installation has a public URL, you'll need to configure one of your Nexmo numbers to used that URL as the 
[inbound message webhook][webhook].

Now just send a 'join` command to that number from your phone, and watch the php error log for debug info.
    
[config]: config.dist.php    
[tutorial]: https://www.nexmo.com/blog/    
[php_lib]: https://github.com/Nexmo/nexmo-php    
[ngrok]: https://ngrok.com/
[webhook]: https://docs.nexmo.com/messaging/setup-callbacks#setting?utm_source=DEV_REL&utm_medium=github&utm_campaign=nexmo-sms-group-chat 
[mLab]: https://mlab.com/plans/pricing/
[compose]: https://app.compose.io/signup/
[account]: https://dashboard.nexmo.com/sign-up?utm_source=DEV_REL&utm_medium=github&utm_campaign=nexmo-sms-group-chat&utm_term=account
[free]: https://dashboard.nexmo.com/sign-up?utm_source=DEV_REL&utm_medium=github&utm_campaign=nexmo-sms-group-chat&utm_term=free
[source]: https://github.com/nexmo-community/nexmo-sms-group-chat/archive/master.zip
