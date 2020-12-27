#!/usr/bin/env python3

from sqlalchemy import sql

from .. import get_connection, get_table

def get_feed_row(feed_id, user_id):
    conn = get_connection()
    feeds = get_table('Feeds')
    display = get_table('Display')

    display_user = sql.select([
            display
    ]).where(
            display.c.UserID == user_id
    ).alias()
    q = sql.select([
            feeds,
            sql.case(
                    [(display_user.c.FeedID != None, True)],
                    else_=False
            ).label('Displayed')
    ]).select_from(
            feeds.outerjoin(display_user)
    ).where(
            feeds.c.FeedID == feed_id
    )

    return conn.execute(q).fetchone()

def get_tag_name(tag_id):
    conn = get_connection()
    tags = get_table('Tags')

    q = sql.select([
            tags.c.Name
    ]).where(
            tags.c.TagID == tag_id
    )

    return conn.execute(q).fetchone()['Name']