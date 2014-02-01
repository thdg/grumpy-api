grumpy-api
==========

JSON API fyrir Grumpy


Notkun
------

Notendur:

    GET  /user/ -> Listi yfir alla notendur (default: start=0, count=10, q=None)
    POST /user/ -> búa til nýjan notanda
    PUT  /user/ -> uppfæra notanda
    
    GET  /user/<user_id>/ -> Upplýsingar um notanda með auðkennið user_id
    GET  /user/exists/?username=<name> -> True ef notandanafn er í tekið annars False
    
Væl:

    GET  /post/ -> Listi yfir öll væl (default: start=0, count=10, q='', user=None)
    POST /post/ -> búa til nýtt væl
    PUT  /post/ -> uppfæra væl
    
    GET  /post/<post_id>/ -> Upplýsingar um væl með auðkennið post_id
    
