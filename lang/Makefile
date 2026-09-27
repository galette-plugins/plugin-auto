INSTALLDIR = $(shell pwd)
LANGUAGES = `find ./ -name "auto_*.po" -print | sed 's|^\./auto_\(.*\).po|\1|'`
DOMAINS = auto
PHP_SOURCES = $(shell find ../ -maxdepth 1 -name \*.php) \
              $(shell find ../lib/GaletteAuto/ -name \*.php) \
              $(shell find ../tempcache -name \*.twig)

all : extract mo

twig :
	cd ../../../../ && bin/console galette:twig-cache $(shell basename $(abspath $(CURDIR)/..)) && cd ${INSTALLDIR}

po :
	@echo "Generating PO files:"
	@for l in ${LANGUAGES}; do \
	  for d in ${DOMAINS}; do \
	    if [ -f $${d}_$${l}.po ]; then \
	      echo -n "  Updating $${d}_$${l}.po"; \
	      msgmerge -U $${d}_$${l}.po $$d.pot >/dev/null ; \
	    else \
	      echo "  Creating of $${d}_$${l}.po"; \
	      msginit -o $${d}_$${l}.po -i $$d.pot >/dev/null ; \
	    fi; \
	  done \
	done

mo :
	cd ../../../../ && bin/console galette:compile-locales plugin-auto

extract : twig ${PHP_SOURCES} Makefile
	xgettext ${PHP_SOURCES} --keyword=_T:1,2t --keyword=__:1,2t --keyword=_Tn:1,2,4t --keyword=_Tx:1c,2,3t --keyword=_Tnx:1c,2,3,5t -L PHP --from-code=UTF-8 --add-comments=TRANS --force-po -o auto.pot;
	cd ../../../../ && bin/console galette:twig-pot-references $(CURDIR)/auto.pot $(shell basename $(abspath $(CURDIR)/..))
	echo "Generating en_US:"
	@for d in ${DOMAINS}; do \
	  LANG=C msginit --no-translator -i $$d.pot --locale=en_US.utf8 -o $${d}_en_US.po; \
	done
