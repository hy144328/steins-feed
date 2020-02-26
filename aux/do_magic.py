#!/usr/bin/env python3

from collections import OrderedDict
from datetime import datetime, timedelta
import json
import numpy as np
import os
import pickle
import sys

dir_path = os.path.abspath(__file__)
dir_path = os.path.dirname(dir_path)
dir_path = os.path.join(dir_path, '..')
dir_path = os.path.abspath(dir_path)

sys.path.append(dir_path)

from steins_log import get_logger
logger = get_logger()
from steins_magic import build_feature, clf_dict, kullback_leibler, steins_learn
from steins_sql import *
conn = get_connection()
c = get_cursor()

users = c.execute("SELECT * FROM Users").fetchall()
for user_it in users:
    user = user_it['Name']
    user_id = user_it['UserID']

    user_path = dir_path + os.sep + str(user_id)
    try:
        os.mkdir(user_path)
    except FileExistsError:
        pass

    timestamp = last_updated()
    timestamp_like = last_liked(user_id)

    for clf_it in clf_dict:
        clf_path = user_path + os.sep + clf_it
        try:
            os.mkdir(clf_path)
        except FileExistsError:
            pass

        file_path = clf_path + os.sep + "clfs.pickle"
        if os.path.exists(file_path) and datetime.utcfromtimestamp(os.stat(file_path).st_mtime) > timestamp_like:
            with open(file_path, 'rb') as f:
                clfs = pickle.load(f)
        else:
            logger.info("Learn {} about {}.".format(clf_it, user))
            clfs = steins_learn(user_id, clf_it)
            if len(clfs) == 0:
                continue
            reset_magic(user_id, clf_it)
            with open(file_path, 'wb') as f:
                pickle.dump(clfs, f)

        for lang_it in clfs:
            pipeline = clfs[lang_it]
            count_vect = pipeline.named_steps['vect']

            # Words.
            #words = list(count_vect.vocabulary_.keys())
            words = list(count_vect.vocabulary_nltk.values())
            coeffs = pipeline.predict_proba(words)[:, 1]
            table = sorted(zip(words, coeffs), key=lambda x: x[1])
            table = OrderedDict(table)

            # If KL divergence below threshold, do not prepare analysis.
            try:
                file_path = clf_path + os.sep + "{}_words.json".format(lang_it)
                with open(file_path, 'r') as f:
                    table_old = json.load(f)
                    div = kullback_leibler(table, table_old)
                    logger.info("Kullback-Leibler divergence of {}, {}, {}: {}.".format(user, clf_it, lang_it, div))

                    if abs(div) < 0.5:
                        continue
            except FileNotFoundError:
                pass

            logger.info("Learn {} about {} ({} words).".format(clf_it, user, lang_it))
            with open(file_path, 'w') as f:
                json.dump(table, f)

            file_path = clf_path + os.sep + "{}_cookie.json".format(lang_it)
            with open(file_path, 'w') as f:
                words = list(count_vect.vocabulary_nltk.keys())
                table = sorted(zip(words, coeffs), key=lambda x: x[1])
                table = OrderedDict(table)
                json.dump(table, f)

            # Feeds.
            logger.info("Learn {} about {} ({} feeds).".format(clf_it, user, lang_it))
            feeds = c.execute("SELECT * FROM Feeds INNER JOIN Display USING (FeedID) WHERE UserID=? AND Language=?", (user_id, lang_it, )).fetchall()

            coeffs = []
            for feed_it in feeds:
                articles_raw = c.execute("SELECT * FROM (Items INNER JOIN Feeds USING (FeedID)) INNER JOIN Display USING (FeedID) WHERE FeedID=? AND Published BETWEEN ? AND ?", (feed_it['FeedID'], timestamp - timedelta(days=100), timestamp, )).fetchall()
                articles = [build_feature(row) for row in articles_raw]

                if len(articles) == 0:
                    coeffs.append(0.5)
                    continue

                articles_proba = pipeline.predict_proba(articles)[:, 1]
                for i in range(len(articles)):
                    c.execute("INSERT OR IGNORE INTO {} (UserID, ItemID, Score) VALUES (?, ?, ?)".format(clf_dict[clf_it]['table']), (user_id, articles_raw[i]['ItemID'], articles_proba[i], ))
                    logger.debug("Score item -- {} ({}).".format(articles_raw[i]['Title'], 2. * articles_proba[i] - 1.))
                conn.commit()

                articles_proba /= (1. - articles_proba)
                articles_proba = np.log(articles_proba)
                coeff = np.sum(articles_proba) / (articles_proba.size + 10.)
                coeff = np.exp(coeff)
                coeff /= (1. + coeff)

                logger.info("Score feed -- {} ({}).".format(feed_it['Title'], 2. * coeff - 1.))
                coeffs.append(coeff)

            table = OrderedDict(sorted(zip([e['FeedID'] for e in feeds], coeffs), key=lambda x: x[1]))

            file_path = clf_path + os.sep + "{}_feeds.json".format(lang_it)
            with open(file_path, 'w') as f:
                json.dump(table, f)

close_connection()
