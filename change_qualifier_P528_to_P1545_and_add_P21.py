#!/usr/bin/python

"""
Licence CC0 1.0
https://creativecommons.org/publicdomain/zero/1.0/
 
Homepage: https://vaalidatahack.github.io/index.html

Overall functionality:
The bot reads csv data regarding municipal elections of Finland
and creates new Wikidata items based on the data.

Input data format:
"Q28778756"	"Eija Loukoila"	"Vuoden 2012 kunnanvaltuutettu, ekonomi, yrittäjä"	"Q1757"	"http://vaalit.yle.fi/tulospalvelu/2012/kuntavaalit/kunnat/helsinki_ehdokkaat_vertauslukujarjestyksessa_1_91.html"	"Helsinki: Ehdokkaat vertauslukujärjestyksessä"	"Q28735596"	"Q1757"	"Q385927"	"707"	"091"	"63"	"61"	"Q6581072"


Result in Wikidata
- https://www.wikidata.org/wiki/Q28789936

Install pywikibot
$ git clone --recursive https://gerrit.wikimedia.org/r/pywikibot/core.git pywikibot-core
$ cd pywikibot-core
$ python generate_user_files.py

Running the script
$ python pwb.py change_qualifier_P528_to_P1545_and_add_P21.py

Code throws an error if there is no configuration (username, password) for 
selected mediawiki site in pywikibot. Also expected result from test.wikidata.org 
for template which is in the code is also error because it uses items and properties 
which only exist in real wikidata.org.

Test code with test.wikidata.org first or with sandbox items in real wikidata. 


"""

import json
import pywikibot
import csv 
from pywikibot.pagegenerators import WikibaseSearchItemPageGenerator

def is_float(s):
    try:
        float(s)
        return True
    except ValueError:
        return False

#site = pywikibot.Site("wikidata", "wikidata") # The real Wikidata site
site = pywikibot.Site("test", "wikidata") # Site for testing code
repo = site.data_repository()

# Source data 	
csvfile=open('fixme.qualifier_3.csv', 'rb')
reader = csv.reader(csvfile, delimiter='\t') # The csv.reader expects input stream
for row in reader:
	pywikibot.output(row[0])

	if len(row) != 14:
		pywikibot.output(len(row))
		pywikibot.output(json.dumps(row))
		exit(1)

	item_id			 = row[0]
        name                     = row[1]    # 'Juha Elo'
        description_fi           = row[2]    # 'Vuoden 2012 kunnanvaltuutettu, yrittäjä, FM'
        p551_wikidata_id         = row[3]    # Q193367 ; asuinpaikka, tässä $kunnan_wikidata_id 
        source_link              = row[4]    # 'http://vaalit.yle.fi/tulospalvelu/2012/kuntavaalit/kunnat/porvoo_ehdokkaat_vertauslukujarjestyksessa_2_638.html'
        source_text              = row[5]    # 'Porvoo: Ehdokkaat vertauslukujärjestyksessä'
        kuntavaali_wikidata_id   = row[6]    # 'Q28753467' ;kuntavaalin wikidata_id
        kunta_wikidata_id        = row[7]    # 'Q1009118' ; kunnan wikidata id
	puolue_wikidata_id       = row[8]    # 'Q634277' ; puolue wikidata_id 
        aanimaara                = row[9]    # 80
        kuntanumero              = row[10]   # '638'
        ehdokasnumero            = row[11]   # '0003'
	ika_tapahtumahetkella    = row[12]
	gender		         = row[13]

	item = pywikibot.ItemPage(repo, item_id)
        item.get()

        if 'P3602' in item.claims:
            to_remove = []
	    if len(item.claims['P3602'])!=1 :
		pywikibot.output("VIRHE P3602 ominaisuuksia on liikaa")
            	exit()

            for claim in item.claims['P3602']:
            	qual_3 = claim.qualifiers[u'P528'][0]
            	claim.removeQualifier(qual_3)

        	p1545 = pywikibot.Claim(repo, 'P1545', isQualifier=True)
	        p1545.setTarget(str(int(ehdokasnumero)))
       		claim.addQualifier(p1545)


        if 'P21' not in item.claims:
            claim = pywikibot.Claim(repo, 'P21', datatype='wikibase-item')
            target = pywikibot.ItemPage(repo, gender)
            claim.setTarget(target)
            item.editEntity({'claims': [claim.toJSON()]}, summary="Adding P21")


# For safety. Remove exit for batch work
        exit()

pywikibot.output("OK")
