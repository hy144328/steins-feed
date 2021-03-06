#!/usr/bin/env python3

import importlib
import json
from html import unescape
from lxml import etree, html
import numpy as np
import os
import pickle
import re

from sklearn.feature_extraction.text import CountVectorizer, TfidfTransformer
from sklearn.pipeline import Pipeline

from steins_nltk import NLTK_CountVectorizer
from steins_sql import get_connection, get_cursor

dir_path = os.path.dirname(os.path.abspath(__file__))
with open(dir_path + os.sep + "json/steins_magic.json", 'r') as f:
    clf_dict = json.load(f)

no_words = 20
no_feeds = 10
no_articles = 100

def build_feature(row):
    title = "<div>" + row['Title'] + " " + row['Summary'] + "</div>"
    title = html.fromstring(title)
    etree.strip_tags(title, "*")
    title = unescape(unescape(html.tostring(title).decode()))
    return title[len("<div>"):-len("</div>")]

def steins_learn(user_id, classifier):
    c = get_cursor()

    clfs = dict()
    langs = [e[0] for e in c.execute("SELECT DISTINCT Feeds.Language FROM (Items INNER JOIN Feeds USING (FeedID)) INNER JOIN Like USING (ItemID) WHERE UserID=? AND Score!=0", (user_id, ))]

    for lang_it in langs:
        likes = c.execute("SELECT Items.* FROM (Items INNER JOIN Feeds USING (FeedID)) INNER JOIN Like USING (ItemID) WHERE UserID=? AND Language=? AND Score=1", (user_id, lang_it, )).fetchall()
        dislikes = c.execute("SELECT Items.* FROM (Items INNER JOIN Feeds USING (FeedID)) INNER JOIN Like USING (ItemID) WHERE UserID=? AND Language=? AND Score=-1", (user_id, lang_it, )).fetchall()
        if not likes or not dislikes:
            continue

        titles = []
        titles += [build_feature(row_it) for row_it in likes]
        titles += [build_feature(row_it) for row_it in dislikes]

        targets = []
        targets += [1 for row_it in likes]
        targets += [-1 for row_it in dislikes]

        # Build pipeline.
        #count_vect = ('vect', CountVectorizer())
        count_vect = ('vect', NLTK_CountVectorizer(lang_it))
        tfidf_transformer = ('tfidf', TfidfTransformer())

        classifier_it = clf_dict[classifier]
        module_ = importlib.import_module(classifier_it['module'])
        class_ = getattr(module_, classifier_it['class'])
        clf = ('clf', class_())

        text_clf = Pipeline([count_vect, tfidf_transformer, clf])

        # Train classifier.
        try:
            clf = text_clf.fit(titles, targets)
        except ValueError:
            continue
        clfs[lang_it] = clf

    return clfs

def steins_predict(user_id, classifier, items):
    conn = get_connection()
    c = get_cursor()

    user_path = dir_path + os.sep + str(user_id)
    clf_path = user_path + os.sep + classifier
    file_path = clf_path + os.sep + "clfs.pickle"

    clfs = []
    try:
        with open(file_path, 'rb') as f:
            clfs = pickle.load(f)
    except:
        pass

    articles = c.execute("SELECT *, Feeds.Language FROM Items INNER JOIN Feeds USING (FeedID) WHERE ItemID IN ({})".format(", ".join(map(str, items)))).fetchall()
    articles = [next(a_it for a_it in articles if a_it['ItemID'] == item_it) for item_it in items]
    articles_proba = np.full(len(items), 0.5)

    for lang_it in clfs:
        idx_it = []
        articles_it = []
        for i in range(len(articles)):
            if articles[i]['Language'] == lang_it:
                idx_it.append(i)
                articles_it.append(build_feature(articles[i]))
        if not idx_it:
            continue

        proba_it = clfs[lang_it].predict_proba(articles_it)
        for i in range(len(idx_it)):
            c.execute("INSERT OR IGNORE INTO {} (UserID, ItemID, Score) VALUES (?, ?, ?)".format(clf_dict[classifier]['table']), (user_id, items[idx_it[i]], proba_it[i][1], ))
            articles_proba[idx_it[i]] = proba_it[i][1]

        conn.commit()

    return articles_proba.tolist()

def kullback_leibler(q, p):
    ev_q = np.sum(list(q.values()))
    ev_p = np.sum(list(p.values()))

    res = 0.
    for k in set(p) & set(q):
        pq = q[k] / ev_q
        pp = p[k] / ev_p
        res += pp * np.log(pp / pq)

    return res
