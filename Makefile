.PHONY: run
run: steins.db
	cd ..; php -S localhost:8000

steins.db:
	python3 aux/init_db.py
	php -f aux/init_feeds.php
	python3 aux/update_db.py

.PHONY: test
test:
	make distclean
	make run

README.pdf: README.md
	pandoc -o README.pdf README.md

.PHONY: clean
clean:
	-rm cachegrind.out
	-rm README.pdf
	-rm steins-*.html
	-rm tmp_feeds.xml
	-rm -r __pycache__/
	-rm *.pyc
	-rm [0-9]*

.PHONY: distclean
distclean:
	make clean
	-rm steins.db
	-rm steins.db.?
	-rm steins.db-journal
	-rm steins.log
	-rm steins_feed.log
	-rm steins_magic.log
