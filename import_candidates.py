#!/usr/bin/python

"""
Licence CC0 1.0
https://creativecommons.org/publicdomain/zero/1.0/
 
Homepage: https://vaalidatahack.github.io/index.html

Overall functionality:
The bot reads csv data regarding municipal elections of finland
and creates new Wikidata items based on the data.

Input data format:
"Vuokko Vakkuri"	"Vuoden 2012 kunnanvaltuutettu, lähihoitaja"	"Q984930"	"http://vaalit.yle.fi/tulospalvelu/2012/kuntavaalit/kunnat/ylitornio_ehdokkaat_vertauslukujarjestyksessa_15_976.html"	"Ylitornio: Ehdokkaat vertauslukujärjestyksessä"	"Q28753630"	"Q984930"	"Q385927"	"146"	"976"	"0068"

Result in Wikidata
- https://www.wikidata.org/wiki/Q28789936

Install pywikibot
$ git clone --recursive https://gerrit.wikimedia.org/r/pywikibot/core.git pywikibot-core
$ cd pywikibot-core
$ python generate_user_files.py

Running the script
$ python pwb.py import_candidates.py

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
csvfile=open('candidates.csv', 'rb')
reader = csv.reader(csvfile, delimiter='\t') # The csv.reader expects input stream
for row in reader:
	pywikibot.output(row[0])

	if len(row) != 11:
		pywikibot.output(len(row))
		pywikibot.output(json.dumps(row))
		exit(1)
	
        name                     = row[0]    # 'Juha Elo'
        description_fi           = row[1]    # 'Vuoden 2012 kunnanvaltuutettu, yrittäjä, FM'
        p551_wikidata_id         = row[2]    # Q193367 ; asuinpaikka, tässä $kunnan_wikidata_id 
        source_link              = row[3]    # 'http://vaalit.yle.fi/tulospalvelu/2012/kuntavaalit/kunnat/porvoo_ehdokkaat_vertauslukujarjestyksessa_2_638.html'
        source_text              = row[4]    # 'Porvoo: Ehdokkaat vertauslukujärjestyksessä'
        kuntavaali_wikidata_id   = row[5]    # 'Q28753467' ;kuntavaalin wikidata_id
        kunta_wikidata_id        = row[6]    # 'Q1009118' ; kunnan wikidata id
	puolue_wikidata_id       = row[7]    # 'Q634277' ; puolue wikidata_id 
        aanimaara                = row[8]    # 80
        kuntanumero              = row[9]    # '638'
        ehdokasnumero            = row[10]   # '0003'

	# How to create own template:
        #
	# 1.) Create first the item with suitable content to Wikidata by hand 
        # 2.) Export it: https://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q28789936
        # 3.) Remove the hash id:s and 'numeric-id' values and select labels, descriptions, claims, aliases and langlinks 

        data = {
            'labels': {
                'en': {
                    'language': 'en',
                    'value': name,
                },
                'sv': {
                    'language': 'sv',
                    'value': name,
                },
                'fi': {
                    'language': 'fi',
                    'value': name,
                }
            },
            'descriptions': {
                'fi': {
                    'language': 'fi',
                    'value': description_fi ,
                }
            },
            "claims":  {
            	"P31": [{                                                     # P31 = Esiintymä kohteesta
            		"mainsnak": {
            			"snaktype": "value",
            			"property": "P31",
            			"datavalue": {
            				"value": {
            					"entity-type": "item",
            					"id": "Q5"                    # Q5 = Ihminen
            				},
            				"type": "wikibase-entityid"
            			},
            			"datatype": "wikibase-item"
            		},
            		"type": "statement",
            		"rank": "normal"
            	}],
            	"P551": [{
            		"mainsnak": {
            			"snaktype": "value",
            			"property": "P551",                           # P551 = Asuinpaikka
            			"datavalue": {
            				"value": {
            					"entity-type": "item",
            					"id": p551_wikidata_id
            				},
            				"type": "wikibase-entityid"
            			},
            			"datatype": "wikibase-item"
            		},
            		"type": "statement",
            		"qualifiers": {
            			"P585": [{
            				"snaktype": "value",
            				"property": "P585",                    # P585 =  Ajankohta
            				"datavalue": {
            					"value": {
            						"time": "+2012-10-28T00:00:00Z",
            						"timezone": 0,
            						"before": 0,
            						"after": 0,
            						"precision": 9,
            						"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
            					},
            					"type": "time"
            				},
            				"datatype": "time"
            			}]
            		},
            		"qualifiers-order": [
            			"P585"
            		],
            		"rank": "normal",
            		"references": [{
            			"snaks": {
            				"P854": [{                             # P854 = Lähde URL
            					"snaktype": "value",
            					"property": "P854",
            					"datavalue": {
            						"value": source_link,
            						"type": "string"
            					},
            					"datatype": "url"
            				}],
            				"P123": [{
            					"snaktype": "value",
            					"property": "P123",            # P123 = Julkaisija 
            					"datavalue": {                 # Q54718 = Yleisradio
            						"value": {
            							"entity-type": "item",
            							"id": "Q54718"
            						},
            						"type": "wikibase-entityid"
            					},
            					"datatype": "wikibase-item"
            				}],
            				"P1476": [{
            					"snaktype": "value",
            					"property": "P1476",            # P1476 = Otsikko
            					"datavalue": {
            						"value": {
            							"text": source_text,
            							"language": "fi"
            						},
            						"type": "monolingualtext"
            					},
            					"datatype": "monolingualtext"
            				}],
            				"P813": [{
            					"snaktype": "value",
            					"property": "P813",              # P813 = Viittauspäivä
            					"datavalue": {
            						"value": {
            							"time": "+2017-02-14T00:00:00Z",
            							"timezone": 0,
            							"before": 0,
            							"after": 0,
            							"precision": 11,
            							"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
            						},
            						"type": "time"
            					},
            					"datatype": "time"
            				}]
            			}
            		}]
            	}],
            	"P39": [{
            		"mainsnak": {
            			"snaktype": "value",
            			"property": "P39",                                # P39 = Tehtävä tai virka
            			"datavalue": {
            				"value": {                                # Q18694248 = kunnanvaltuutettu
            					"entity-type": "item",
            					"id": "Q18694248"
            				},
            				"type": "wikibase-entityid"
            			},
            			"datatype": "wikibase-item"
            		},
            		"type": "statement",
            		"qualifiers": {
            			"P2715": [{
            				"snaktype": "value",
            				"property": "P2715",                      # P2715 = Valittu
            				"datavalue": {
            					"value": {
            						"entity-type": "item",
            						"id": kuntavaali_wikidata_id
            					},
            					"type": "wikibase-entityid"
            				},
            				"datatype": "wikibase-item"
            			}]
            		},
            		"qualifiers-order": [
            			"P2715"
            		],
            		"rank": "normal",
            		"references": [{
            			"snaks": {
            				"P854": [{
            					"snaktype": "value",
            					"property": "P854",                 # P854 = lähde URL
            					"datavalue": {
            						"value": source_link,
            						"type": "string"
            					},
            					"datatype": "url"
            				}],
            				"P123": [{
            					"snaktype": "value",
            					"property": "P123",                 # P123 = julkaisija
            					"datavalue": {
            						"value": {
            							"entity-type": "item",
            							"id": "Q54718"      # Q54718 = Yleisradio
            						},
            						"type": "wikibase-entityid"
            					},
            					"datatype": "wikibase-item"
            				}],
            				"P1476": [{
            					"snaktype": "value",
            					"property": "P1476",                # P1476 = Otsikko
            					"datavalue": {
            						"value": {
            							"text": source_text,
            							"language": "fi"
            						},
            						"type": "monolingualtext"
            					},
            					"datatype": "monolingualtext"
            				}],
            				"P813": [{
            					"snaktype": "value",
            					"property": "P813",                 # P813 = päiväys
            					"datavalue": {
            						"value": {
            							"time": "+2017-02-14T00:00:00Z",
            							"timezone": 0,
            							"before": 0,
            							"after": 0,
            							"precision": 11,
            							"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
            						},
            						"type": "time"
            					},
            					"datatype": "time"
            				}]
            			}
            		}]
            	}],
            	"P726": [{
            		"mainsnak": {                                              # P726 = Ehdokas
            			"snaktype": "value",
            			"property": "P726",
            			"datavalue": {
            				"value": {
            					"entity-type": "item",
            					"id": kuntavaali_wikidata_id
            				},
            				"type": "wikibase-entityid"
            			},
            			"datatype": "wikibase-item"
            		},
            		"type": "statement",
            		"qualifiers": {
            			"P3602": [{                                      # P3602 = Ehdokkaana vaaleissa
            				"snaktype": "value",
            				"property": "P3602",
            				"datavalue": {
            					"value": {
            						"entity-type": "item",
            						"id": kunta_wikidata_id
            					},
            					"type": "wikibase-entityid"
            				},
            				"datatype": "wikibase-item"
            			}],
            			"P1268": [{
            				"snaktype": "value",
            				"property": "P1268",                     # P1268 = Edustaa järjestöä
            				"datavalue": {
            					"value": {
            						"entity-type": "item",
            						"id": puolue_wikidata_id
            					},
            					"type": "wikibase-entityid"
            				},
            				"datatype": "wikibase-item"
            			}],
            			"P1111": [{
            				"snaktype": "value",
            				"property": "P1111",                     #P1111 = Äänimäärä
            				"datavalue": {
            					"value": {
            						"amount": aanimaara,
            						"unit": "1"
            					},
            					"type": "quantity"
            				},
            				"datatype": "quantity"
            			}],
            			"P1203": [{
            				"snaktype": "value",
            				"property": "P1203",                     # P1203 = Kuntanumero
            				"datavalue": {
            					"value": kuntanumero,
            					"type": "string"
            				},
            				"datatype": "external-id"
            			}],
            			"P528": [{
            				"snaktype": "value",
            				"property": "P528",                      # P528 = luettelotunnus
            				"datavalue": {
            					"value": ehdokasnumero,
            					"type": "string"
            				},
            				"datatype": "string"
            			}]
            		},
            		"rank": "normal",
            		"references": [{
            			"snaks": {
            				"P854": [{
            					"snaktype": "value",
            					"property": "P854",               # P854 = Lähde URL
            					"datavalue": {
            						"value": source_link,
            						"type": "string"
            					},
            					"datatype": "url"
            				}],
            				"P123": [{
            					"snaktype": "value",
            					"property": "P123",               # P123 = julkaisija
            					"datavalue": {
            						"value": {
            							"entity-type": "item",
            							"id": "Q54718"    # Q54718 = Yleisradio
            						},
            						"type": "wikibase-entityid"
            					},
            					"datatype": "wikibase-item"
            				}],
            				"P1476": [{
            					"snaktype": "value",
            					"property": "P1476",              # P1476 = Otsikko
            					"datavalue": {
            						"value": {
            							"text": source_text,
            							"language": "fi"
            						},
            						"type": "monolingualtext"
            					},
            					"datatype": "monolingualtext"
            				}],
            				"P813": [{
            					"snaktype": "value",
            					"property": "P813",                # P813
            					"datavalue": {
            						"value": {
            							"time": "+2017-02-14T00:00:00Z",
            							"timezone": 0,
            							"before": 0,
            							"after": 0,
            							"precision": 11,
            							"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
            						},
            						"type": "time"
            					},
            					"datatype": "time"
            				}]
            			}
            		}]
            	}]
            }
        }

        item = pywikibot.ItemPage(repo)
        item.editEntity(data)

# For safety. Remove exit for batch work
        exit()
pywikibot.output("OK")
