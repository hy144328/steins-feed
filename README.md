# Stein's Feed

Content aggregator.
Icon taken from [Santa Fe College](https://www.sfcollege.edu/about/index).

## Packages

Python 3.7:

*   lxml
*   feedparser
*   nltk
*   requests
*   scikit-learn

PHP:

*   SimpleXML
*   SQLite3

## Run on desktop

1.  Enter `python3 steins_server.py` on command line.
2.  Open browser, and enter `localhost:8000`.
3.  Enjoy.
4.  Close browser.
5.  Enter `^C` on command line.

## Run on web server

1.  Enter git clone `git@github.com:hy144328/steins-feed` on command line.
2.  Move to site on web server, e.g. `/var/www/html/steins-feed`.
3.  Make site available.
    For Apache:

        Alias /steins-feed "/var/www/html/steins-feed/"

        <Directory /var/www/html/steins-feed/>
            Options +FollowSymlinks
            AllowOverride All

            SetEnv HOME /var/www/html/steins-feed
            SetEnv HTTP_HOME /var/www/html/steins-feed
        </Directory>

    Enable it in server config.
4.  Make sure PHP scripts are accessible (`.htaccess` included).
5.  Set up a cron job, e.g. hourly:

        00 * * * * python3 /var/www/html/steins-feed/steins_feed.py

6.  Open browser, and enter URL to website.
7.  Enjoy.

## No RSS

*   Bloomberg.
*   Business Insider.
*   New York Magazine.
*   The Tab.
*   The Telegraph.
*   The Times.
