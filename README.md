grumpy-api
==========

JSON API fyrir Grumpy


Notkun
------

TODO: bæta við access_token ef þess þarf þar sem það á við

Login:

	POST /login/ -> True ef innskráning tókst, annars False (usename, password)
	POST /signout/ -> True ef útskráning tóks, annars False (username)

Notendur:

    GET  /user/ -> Listi yfir alla notendur sem passa við query (q, start=0, count=10)
    POST /user/ -> búa til nýjan notanda
    PUT  /user/ -> uppfæra notanda
    
    GET  /user/<user_id>/ -> Upplýsingar um notanda með auðkennið user_id
    GET  /user/exists/?username=<name> -> True ef notandanafn er í tekið annars False
    
Tuð:

    GET  /post/ -> Listi yfir öll tuð (user, start=0, count=10)
    POST /post/ -> búa til nýtt tuð
    PUT  /post/ -> uppfæra tuð
    
    GET  /post/<post_id>/ -> Upplýsingar um tuð með auðkennið post_id
    
