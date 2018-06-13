#!/usr/bin/env python3

import os

from lxml import etree

def add_feed(c, title, link):
    c.execute("INSERT INTO Feeds (Title, Link) VALUES (?, ?)", (title, link, ))

def delete_feed(c, title):
    c.execute("DELETE FROM Feeds WHERE Title='?'", (title, ))

def init_feeds(c):
    dir_name = os.path.dirname(os.path.abspath(__file__))
    f = open(dir_name+os.sep+"feeds.xml", 'r')
    tree = etree.fromstring(f.read())
    f.close()

    feed_list = tree.xpath("//feed")
    for feed_it in feed_list:
        title = feed_it.xpath("./title")[0].text
        link = feed_it.xpath("./link")[0].text
        add_feed(c, title, link)
