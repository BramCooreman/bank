SELECT  		  kuka.yhtio              AS yhtio
                         , yhtio.nimi            AS yhtionNimi
                         , yhtio.ytunnus         AS ytunnus
                 FROM kuka
                 JOIN            yhtio
                 ON              kuka.yhtio = yhtio.yhtio
                 WHERE kuka = 'valkku 1'
                 ORDER BY yhtionNimi ASC;
