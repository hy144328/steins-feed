#!/usr/bin/env python3

from lxml import etree
import os

dir_path = os.path.dirname(os.path.abspath(__file__))

from steins_log import get_logger
logger = get_logger()
from steins_sql import *

def load_config(file_path):
    logger.info("Initialize feeds -- {}.".format(file_path))

    with open(file_path, 'r') as f:
        tree = etree.fromstring(f.read())
    feed_list = tree.xpath("//feed")

    conn = get_connection()
    c = conn.cursor()

    for feed_it in feed_list:
        title = feed_it.xpath("./title")[0].text
        link = feed_it.xpath("./link")[0].text
        try:
            lang = feed_it.xpath("./lang")[0].text
        except IndexError:
            lang = ''
        try:
            summary = feed_it.xpath("./summary")[0].text
        except IndexError:
            summary = 2
        add_feed(title, link, lang, summary)

    conn.commit()

def export_config(file_path):
    c = get_cursor()

    with open(file_path, 'w') as f:
        f.write("<?xml version=\"1.0\"?>\n\n")
        f.write("<root>\n")
        for feed_it in c.execute("SELECT * FROM Feeds".format(user)).fetchall():
            f.write("    <feed>\n")
            f.write("        <title>{}</title>\n".format(escape(feed_it['Title'])))
            f.write("        <link>{}</link>\n".format(escape(feed_it['Link'])))
            f.write("        <lang>{}</lang>\n".format(escape(feed_it['Language'])))
            f.write("        <summary>{}</summary>\n".format(feed_it['Summary']))
            f.write("    </feed>\n")
        f.write("</root>\n")

def handle_load_config(qd):
    load_config(dir_path + os.sep + "tmp_feeds.xml", qd['user'])

def handle_export_config(qd):
    export_config(dir_path + os.sep + "tmp_feeds.xml", qd['user'])
