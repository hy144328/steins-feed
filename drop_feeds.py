#!/usr/bin/env python3

import sqlite3

conn = sqlite3.connect("steins.db")
c = conn.cursor()
c.execute("DROP TABLE Feeds")
conn.commit()
conn.close()
