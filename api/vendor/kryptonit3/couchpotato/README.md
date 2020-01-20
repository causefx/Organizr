# CouchPotato
PHP Wrapper for CouchPotato API https://couchpota.to/

You may view the CouchPotato API documentation by visiting your CouchPotato URL and then appending `/docs` to the URL. Example: `http://127.0.0.1:5050/docs

## Installation
```ruby
composer require kryptonit3/couchpotato
```

## Example Usage
```php
use Kryptonit3\CouchPotato\CouchPotato;
```
```php
public function addMovie()
{
    $couchpotato = new CouchPotato('http://127.0.0.1:8989', 'cf7544f71b6c4efcbb84b49011fc965c'); // URL and API Key
    
    return $couchpotato->getMovieAdd([
        'identifier' => 'tt0076759' // IMDB ID
    ]);
}
```
### HTTP Auth
If your site requires HTTP Auth username and password you may supply it like this. Please note, if you are using HTTP Auth without SSL you are sending your username and password unprotected across the internet.
```php
$couchpotato = new CouchPotato('http://127.0.0.1:5050', 'cf7544f71b6c4efcbb84b49011fc965c', 'my-username', 'my-password');
```

### Output
```json
{
  "movie": {
    "status": "active",
    "info": {
      "rating": {
        "imdb": [
          8.7,
          859715
        ]
      },
      "genres": [
        "Adventure",
        "Action",
        "Science Fiction",
        "Fantasy",
        "Sci-Fi"
      ],
      "tmdb_id": 11,
      "plot": "Princess Leia is captured and held hostage by the evil Imperial forces in their effort to take over the galactic Empire. Venturesome Luke Skywalker and dashing captain Han Solo team together with the loveable robot duo R2-D2 and C-3PO to rescue the beautiful princess and restore peace and justice in the Empire.",
      "tagline": "A long time ago in a galaxy far, far away...",
      "original_title": "Star Wars: Episode IV - A New Hope",
      "actor_roles": {
        "Peter Sturgeon": "Sai'torr Kal Fas (uncredited)",
        "Jack Klaff": "Red Four (John \"D\")",
        "Salo Gardner": "Cantina Patron (uncredited)",
        "Fred Wood": "Cantina Patron (uncredited)",
        "Tim Condren": "Stormtrooper (uncredited)",
        "Phil Tippett": "Cantina Alien (uncredited)",
        "Christine Hewett": "Brea Tonnika (uncredited)",
        "Diana Sadley Way": "Thuku (uncredited)",
        "Alex McCrindle": "General Dodonna",
        "Harry Fielder": "Death Star Trooper (uncredited)",
        "Joe Kaye": "Solomohal (uncredited)",
        "Roy Straite": "Cantina Patron (uncredited)",
        "Robert A. Denham": "Hrchek Kal Fas (uncredited)",
        "Phil Brown": "Uncle Owen",
        "Carrie Fisher": "Princess Leia Organa",
        "Shelagh Fraser": "Aunt Beru",
        "Mark Austin": "Boba Fett (special edition) (uncredited)",
        "Graham Ashley": "Gold Five",
        "Erica Simmons": "Tawss Khaa (uncredited)",
        "Ted Gagliano": "Stormtrooper with Binoculars (uncredited)",
        "Mark Hamill": "Luke Skywalker",
        "Steve 'Spaz' Williams": "Mos Eisley Citizen (special edition) (uncredited)",
        "Robert Davies": "Cantina Patron (uncredited)",
        "Anthony Lang": "BoShek (uncredited)",
        "Leslie Schofield": "Commander #1",
        "David Prowse": "Darth Vader",
        "Steve Gawley": "Death Star Trooper (uncredited)",
        "Laine Liska": "Muftak \/ Cantina Band Member (uncredited)",
        "Isaac Grand": "Cantina Patron (uncredited)",
        "Jeremy Sinden": "Gold Two",
        "Geoffrey Moon": "Cantina Patron (uncredited)",
        "Garrick Hagon": "Red Three (Biggs)",
        "Kim Falkinburg": "Djas Puhr (uncredited)",
        "Derek Lyons": "Temple Guard \/ Medal Bearer (uncredited)",
        "John Sylla": "Cantina Voices (voice) (uncredited)",
        "Melissa Kurtz": "Jawa (uncredited)",
        "Maria De Aragon": "Greedo (uncredited)",
        "Doug Beswick": "Cantina Alien (uncredited)",
        "Janice Burchette": "Nabrun Leids (uncredited)",
        "Paul Blake": "Greedo (uncredited)",
        "James Earl Jones": "Voice of Darth Vader (voice)",
        "Alf Mangan": "Takeel (uncredited)",
        "Linda Jones": "Chall Bekan (uncredited)",
        "Peter Sumner": "Lt. Pol Treidum (uncredited)",
        "Al Lampert": "Daine Jir (uncredited)",
        "Harrison Ford": "Han Solo",
        "Annette Jones": "Mosep (uncredited)",
        "Alan Harris": "Leia's Rebel Escort (uncredited)",
        "Richard LeParmentier": "General Motti",
        "Lightning Bear": "Stormtrooper (uncredited)",
        "Pam Rose": "Leesub Sirln (uncredited)",
        "Don Henderson": "General Taggi",
        "George Roubicek": "Cmdr. Praji (Imperial Officer #2 on rebel ship) (uncredited)",
        "Frazer Diamond": "Jawa (uncredited)",
        "Drewe Henley": "Red Leader (as Drewe Hemley)",
        "William Hootkins": "Red Six (Porkins)",
        "Nelson Hall": "Stormtrooper (special edition) (uncredited)",
        "Joe Johnston": "Death Star Trooper (uncredited)",
        "Anthony Daniels": "See Threepio (C-3PO)",
        "Eddie Byrne": "General Willard",
        "Angela Staines": "Senni Tonnika (uncredited)",
        "Barry Gnome": "Kabe (uncredited)",
        "Peter Diamond": "Stormtrooper \/ Tusken Raider \/ Death Star Trooper \/ Garouf Lafoe (uncredited)",
        "Arthur Howell": "Stormtrooper (uncredited)",
        "Jon Berg": "Cantina Alien (uncredited)",
        "Marcus Powell": "Rycar Ryjerd (uncredited)",
        "Rick McCallum": "Stormtrooper (special edition) (uncredited)",
        "Rusty Goffe": "Kabe \/ Jawa \/ GONK Droid (uncredited)",
        "Harold Weed": "Ketwol \/ Melas (uncredited)",
        "Colin Michael Kitchens": "Stormtrooper (voice) (uncredited)",
        "Frank Henson": "Stormtrooper (uncredited)",
        "Burnell Tucker": "Del Goren (uncredited)",
        "George Stock": "Cantina Patron (uncredited)",
        "Hal Wamsley": "Jawa (uncredited)",
        "Mahjoub": "Jawa (uncredited)",
        "John Chapman": "Drifter (Red 12) (uncredited)",
        "Alec Guinness": "Ben (Obi-Wan) Kenobi",
        "Ted Burnett": "Wuher (uncredited)",
        "Alfie Curtis": "Dr. Evazan (uncredited)",
        "Peter Cushing": "Grand Moff Tarkin",
        "Tiffany L. Kurtz": "Jawa (uncredited)",
        "Mandy Morton": "Swilla Corey (uncredited)",
        "Tom Sylla": "Massassi Outpost Announcer \/ Various Voices (voice) (uncredited)",
        "Kenny Baker": "Artoo-Detoo (R2-D2)",
        "Shane Rimmer": "InCom Engineer (uncredited)",
        "Morgan Upton": "Stormtrooper (voice) (uncredited)",
        "Tommy Ilsley": "Ponda Baba (uncredited)",
        "Jerry Walter": "Stormtrooper (voice) (uncredited)",
        "Gilda Cohen": "Cantina Patron (uncredited)",
        "Reg Harding": "Stormtrooper (uncredited)",
        "Angus MacInnes": "Gold Leader (as Angus McInnis)",
        "Malcolm Tierney": "Lt. Shann Childsen (uncredited)",
        "Peter Mayhew": "Chewbacca",
        "Bill Weston": "Stormtrooper (uncredited)",
        "Warwick Diamond": "Jawa (uncredited)",
        "Scott Beach": "Stormtrooper (voice) (uncredited)",
        "Jack Purvis": "Chief Jawa",
        "Larry Ward": "Greedo (voice) (uncredited)",
        "Denis Lawson": "Red Two (Wedge) (as Dennis Lawson)",
        "David Ankrum": "Red Two (voice) (uncredited)",
        "Barry Copping": "Wioslea (uncredited)",
        "Sadie Eden": "Garindan (uncredited)",
        "Lorne Peterson": "Massassi Base Rebel Scout (uncredited)",
        "Grant McCune": "Death Star Gunner (uncredited)"
      },
      "via_imdb": true,
      "mpaa": "PG",
      "via_tmdb": true,
      "directors": [
        "George Lucas"
      ],
      "titles": [
        "Star Wars",
        "La guerra de las galaxias. Episodio IV: Una nueva esperanza",
        "Star Wars, Episode IV - Un nouvel espoir",
        "Gwiezdne wojny: Cz\u0119\u015b\u0107 IV - Nowa nadzieja",
        "Star Wars Episode IV - A New Hope",
        "Star Wars Episode 4 - A New Hope",
        "Star Wars Episode IV",
        "Star Wars Episode IV",
        "Star Wars Episode IV - Eine neue Hoffnung",
        "Star Wars: Episode IV - Et Nytt H\u00e5p",
        "Guerra nas Estrelas - Epis\u00f3dio IV - Uma Nova Esperan\u00e7a",
        "\u661f\u969b\u5927\u6230\uff1a\u66d9\u5149\u4e4d\u73fe",
        "Star Wars: Episode 4 - Eine neue Hoffnung",
        "Star Wars Episode 4",
        "Star Wars 4",
        "Star Wars IV - Eine neue Hoffnung",
        "Star Wars: Episode IV - A New Hope - Despecialized Edition",
        "Star Wars IV. r\u00e9sz: Egy \u00faj rem\u00e9ny",
        "Krieg der Sterne",
        "Star Wars, Episodio IV: Una Nueva Esperanza",
        "La guerra de las galaxias",
        "Star Wars Epis\u00f3dio IV - Uma Nova Esperan\u00e7a",
        "Guerre stellari",
        "\uc2a4\ud0c0 \uc6cc\uc988: \uc5d0\ud53c\uc18c\ub4dc 4 - \uc0c8\ub85c\uc6b4 \ud76c\ub9dd",
        "Guerre Stellari: Episodio IV - Una nuova speranza",
        "Star Wars: Episodio IV - Una nuova speranza",
        "Star Wars - Episodio IV - Una nuova speranza",
        "Star Wars Episodio IV Una nuova speranza",
        "La guerre des \u00e9toiles",
        "Star wars : Episode IV - Un nouvel espoir",
        "Star Wars: Episode IV - Eine neue Hoffnung - Despecialized Edition",
        "Stjernekrigen",
        "Star Wars: Episode IV - Et nyt h\u00e5b",
        "\u0417\u0432\u0435\u0437\u0434\u043d\u044b\u0435 \u0432\u043e\u0439\u043d\u044b: \u041d\u043e\u0432\u0430\u044f \u043d\u0430\u0434\u0435\u0436\u0434\u0430",
        "\u039f \u03a0\u03cc\u03bb\u03b5\u03bc\u03bf\u03c2 \u03a4\u03c9\u03bd \u0386\u03c3\u03c4\u03c1\u03c9\u03bd: \u0395\u03c0\u03b5\u03b9\u03c3\u03cc\u03b4\u03b9\u03bf 4 - \u039c\u03b9\u03b1 \u039d\u03ad\u03b1 \u0395\u03bb\u03c0\u03af\u03b4\u03b1",
        "Star Wars: Episode IV - A New Hope",
        "Star Wars 4 - A New Hope",
        "Star Wars IV - A New Hope",
        "Star Wars: IV A New Hope",
        "Star Wars: Episodio IV - Una Nueva Esperanza",
        "Star Wars - Episode IV - A New Hope - Despecialized Edition",
        "A New Hope"
      ],
      "imdb": "tt0076759",
      "year": 1977,
      "images": {
        "disc_art": [
          
        ],
        "poster": [
          "https:\/\/image.tmdb.org\/t\/p\/w154\/tvSlBzAdRE29bZe5yYWrJ2ds137.jpg",
          "http:\/\/ia.media-imdb.com\/images\/M\/MV5BMTU4NTczODkwM15BMl5BanBnXkFtZTcwMzEyMTIyMw@@._V1_.jpg",
          "http:\/\/ia.media-imdb.com\/images\/M\/MV5BMTU4NTczODkwM15BMl5BanBnXkFtZTcwMzEyMTIyMw@@._V1_SX300.jpg"
        ],
        "extra_thumbs": [
          
        ],
        "poster_original": [
          "https:\/\/image.tmdb.org\/t\/p\/original\/tvSlBzAdRE29bZe5yYWrJ2ds137.jpg"
        ],
        "landscape": [
          
        ],
        "actors": {
          "Ted Burnett": "https:\/\/image.tmdb.org\/t\/p\/w185\/A1GOhCu6LH4fg1VlyOT08NjWmJU.jpg",
          "Richard LeParmentier": "https:\/\/image.tmdb.org\/t\/p\/w185\/u0xNmE4QAS5EUbn7tG44EfYUkib.jpg",
          "Lightning Bear": "https:\/\/image.tmdb.org\/t\/p\/w185\/RWJgw1QvH1rgIzGTM6QYwRiQRC.jpg",
          "Peter Cushing": "https:\/\/image.tmdb.org\/t\/p\/w185\/iFE9Xi5B0eZcNFqvCx78UUzmUfI.jpg",
          "Tim Condren": "https:\/\/image.tmdb.org\/t\/p\/w185\/b2LuPeiNkiN7YT0mpJ1O0C2BV58.jpg",
          "Alfie Curtis": "https:\/\/image.tmdb.org\/t\/p\/w185\/5jKHKbIF1cEWeG2sPAzHScgGW7n.jpg",
          "Geoffrey Moon": "https:\/\/image.tmdb.org\/t\/p\/w185\/mmSpFa7i6gJVBxETLHyiMLx54ay.jpg",
          "Mandy Morton": "https:\/\/image.tmdb.org\/t\/p\/w185\/2lmVJN1qV5zAYDy4DYwRxvW8nCb.jpg",
          "Pam Rose": "https:\/\/image.tmdb.org\/t\/p\/w185\/uaFDw1Ksx9ctyDKhoxTw4aFRRtu.jpg",
          "Don Henderson": "https:\/\/image.tmdb.org\/t\/p\/w185\/qeOAWEiZ4cXddRziyaJQ2Mt5Mpm.jpg",
          "George Roubicek": "https:\/\/image.tmdb.org\/t\/p\/w185\/ru7WxtpEOkWADyemk3XlK61v5GS.jpg",
          "Salo Gardner": "https:\/\/image.tmdb.org\/t\/p\/w185\/dEDmkjjpqaNcadl2vVwO6osg8Yv.jpg",
          "Fred Wood": "https:\/\/image.tmdb.org\/t\/p\/w185\/iSUYytTzBokyqKhCw4tvQAL74vn.jpg",
          "Frazer Diamond": "https:\/\/image.tmdb.org\/t\/p\/w185\/9e3i9TJ0pp5zbUnDljNuvkjhgCW.jpg",
          "Derek Lyons": "https:\/\/image.tmdb.org\/t\/p\/w185\/oO7dJlNLJhYyqdTsoUQAFXp1UQS.jpg",
          "Drewe Henley": "https:\/\/image.tmdb.org\/t\/p\/w185\/C28FmnpDyhI9BwD6YjagAe1U53.jpg",
          "Kenny Baker": "https:\/\/image.tmdb.org\/t\/p\/w185\/wnTrBdbJr23GWApnmARg0F7Gpja.jpg",
          "William Hootkins": "https:\/\/image.tmdb.org\/t\/p\/w185\/lGPSg64fsqbWS5PUFKsUKLNOqsx.jpg",
          "Phil Tippett": "https:\/\/image.tmdb.org\/t\/p\/w185\/2uQ0B7fN5cDQk17J1X3pxDSf9y.jpg",
          "Steve Gawley": "https:\/\/image.tmdb.org\/t\/p\/w185\/q0XmjHBKRdWsZfMnP3ks30NdzXb.jpg",
          "Nelson Hall": "https:\/\/image.tmdb.org\/t\/p\/w185\/8t1Fx7haF4OYN7ah57FitSeQDLf.jpg",
          "Shelagh Fraser": "https:\/\/image.tmdb.org\/t\/p\/w185\/xNfiibBvknHztEnL0g7dcdrxOKq.jpg",
          "Morgan Upton": "https:\/\/image.tmdb.org\/t\/p\/w185\/c6cHfJSxRl6Z9D2BcYNELq9ZwEZ.jpg",
          "Joe Johnston": "https:\/\/image.tmdb.org\/t\/p\/w185\/dBLdJJo551G2WH0TPv6ze0FC1ei.jpg",
          "Christine Hewett": "https:\/\/image.tmdb.org\/t\/p\/w185\/67ZqL2PGP2o6uLBOOwzZLikwHHp.jpg",
          "Melissa Kurtz": "https:\/\/image.tmdb.org\/t\/p\/w185\/8KFIcrGTYoI7twjwerAnQH1eaum.jpg",
          "Anthony Daniels": "https:\/\/image.tmdb.org\/t\/p\/w185\/cljvryjb3VwTsNR7fjQKjNPMaBB.jpg",
          "Alex McCrindle": "https:\/\/image.tmdb.org\/t\/p\/w185\/6Q1m79FMq444Q6VpmdERSqvwxpX.jpg",
          "Harry Fielder": "https:\/\/image.tmdb.org\/t\/p\/w185\/tVA1eKmQk3RXMLCuDP0cO5r5txJ.jpg",
          "Maria De Aragon": "https:\/\/image.tmdb.org\/t\/p\/w185\/rnaslrjV5ui6cKphksSni3K0TVQ.jpg",
          "Doug Beswick": "https:\/\/image.tmdb.org\/t\/p\/w185\/iKnyfUrS410O4yQShOAqVTw2SyU.jpg",
          "Eddie Byrne": "https:\/\/image.tmdb.org\/t\/p\/w185\/mSwNawI6Ou8m99Y05WjctoTWYUK.jpg",
          "Jack Klaff": "https:\/\/image.tmdb.org\/t\/p\/w185\/6l21oFayFKyyuBEELJEaj3veo21.jpg",
          "Tom Sylla": "https:\/\/image.tmdb.org\/t\/p\/w185\/7oKTyEfvDwS7zOz3wsMU5z51P4P.jpg",
          "Roy Straite": "https:\/\/image.tmdb.org\/t\/p\/w185\/iTTVUQwq9Jit8BmrDYN0dmZXQjG.jpg",
          "Garrick Hagon": "https:\/\/image.tmdb.org\/t\/p\/w185\/lZYitsCPzlwevNuHzqaSZMQiuUa.jpg",
          "Grant McCune": "https:\/\/image.tmdb.org\/t\/p\/w185\/dyYcw0CDPRWZP0upMV0UPdCVTZw.jpg",
          "Gilda Cohen": "https:\/\/image.tmdb.org\/t\/p\/w185\/uSByRJBieeMpIwg4SeqB8XFCy7x.jpg",
          "Peter Diamond": "https:\/\/image.tmdb.org\/t\/p\/w185\/rIf04LU2CsdzdvUJghFVVjdWcm6.jpg",
          "Reg Harding": "https:\/\/image.tmdb.org\/t\/p\/w185\/2saprCiNLI7rpGmNSPjEVvlkxXA.jpg",
          "Phil Brown": "https:\/\/image.tmdb.org\/t\/p\/w185\/exkyN66HiZWJDmpcOza2hWoswOo.jpg",
          "Arthur Howell": "https:\/\/image.tmdb.org\/t\/p\/w185\/rQU7GbmvonJN8SGjcbusEb9M1aG.jpg",
          "Jerry Walter": "https:\/\/image.tmdb.org\/t\/p\/w185\/kxMyDTBi2DpgVnzPgbOJTokpMUy.jpg",
          "Carrie Fisher": "https:\/\/image.tmdb.org\/t\/p\/w185\/oVYiGe4GzgQkoJfdHg8qKqEoWJz.jpg",
          "Tiffany L. Kurtz": "https:\/\/image.tmdb.org\/t\/p\/w185\/8ic5gMUYR5MBtv6FxoFTAZK9OEB.jpg",
          "Mark Austin": "https:\/\/image.tmdb.org\/t\/p\/w185\/3Zocn38GPVYwWSgVEE3jKJvKyNT.jpg",
          "Graham Ashley": "https:\/\/image.tmdb.org\/t\/p\/w185\/wp02ruOjX8AiGMrRD8QEBljgnlA.jpg",
          "Shane Rimmer": "https:\/\/image.tmdb.org\/t\/p\/w185\/ctrIOcWLjOB5rocS0vVHEjbS1Sx.jpg",
          "Malcolm Tierney": "https:\/\/image.tmdb.org\/t\/p\/w185\/fe7Cz6sxTLt9qSQANRpAAaYcPlV.jpg",
          "Jon Berg": "https:\/\/image.tmdb.org\/t\/p\/w185\/q4IxPRLu82E3ppEw02GeejaNGeJ.jpg",
          "Marcus Powell": "https:\/\/image.tmdb.org\/t\/p\/w185\/cNIpsCHwTl5CCtzaqVSfNjsHQe5.jpg",
          "Rick McCallum": "https:\/\/image.tmdb.org\/t\/p\/w185\/iEA5hgOu02fKjfrsrxvvW5ub6q1.jpg",
          "Lorne Peterson": "https:\/\/image.tmdb.org\/t\/p\/w185\/xCIzR3kH76oNJga9gRNAwxPm2yu.jpg",
          "Rusty Goffe": "https:\/\/image.tmdb.org\/t\/p\/w185\/3PE20IWLMKv1r4nIdkLj65ljv28.jpg",
          "Alan Harris": "https:\/\/image.tmdb.org\/t\/p\/w185\/t7bLuzCIGJWn7FRUVoHHQGzijWo.jpg",
          "Alf Mangan": "https:\/\/image.tmdb.org\/t\/p\/w185\/yZ1jthofTE7NCwcJkGMCSjrFpz6.jpg",
          "Bill Weston": "https:\/\/image.tmdb.org\/t\/p\/w185\/apVNA3SRNIrB6gu88nGTEhPewI2.jpg",
          "Angela Staines": "https:\/\/image.tmdb.org\/t\/p\/w185\/ydiU1ozqqeWuSXYMEKyLattGUr0.jpg",
          "Warwick Diamond": "https:\/\/image.tmdb.org\/t\/p\/w185\/3E5Ktz0o6k4Xz6iTq2zCVHsxleX.jpg",
          "Harold Weed": "https:\/\/image.tmdb.org\/t\/p\/w185\/ysocn4XckZRUqZsUp4qwldLCgZk.jpg",
          "Scott Beach": "https:\/\/image.tmdb.org\/t\/p\/w185\/gkt4TpoRR75eTddsny3Qvofe6TY.jpg",
          "Steve 'Spaz' Williams": "https:\/\/image.tmdb.org\/t\/p\/w185\/zD0Qyhjjg87fDdEDJbqOFsSMtjm.jpg",
          "Jack Purvis": "https:\/\/image.tmdb.org\/t\/p\/w185\/tuFTY1jhlEgZm3vM80KdAEvHwNI.jpg",
          "Larry Ward": "https:\/\/image.tmdb.org\/t\/p\/w185\/zRqHcr0ySV1IJiDVmpciCokzn3h.jpg",
          "Ted Gagliano": "https:\/\/image.tmdb.org\/t\/p\/w185\/jpfBK5PYsY13c1gey5HdojwWW8i.jpg",
          "Peter Sumner": "https:\/\/image.tmdb.org\/t\/p\/w185\/3BiflFG5cnHYI1Ehn85PhTyCZaf.jpg",
          "Al Lampert": "https:\/\/image.tmdb.org\/t\/p\/w185\/8YLrP1AQTVtP6G1FJnPsiQOOOO5.jpg",
          "Angus MacInnes": "https:\/\/image.tmdb.org\/t\/p\/w185\/qftkol8hj7yBBP3KCxRWYkhRyLC.jpg",
          "Hal Wamsley": "https:\/\/image.tmdb.org\/t\/p\/w185\/4nRNQyY5TyKNpNIUK6Z9JRr3xWw.jpg",
          "John Sylla": "https:\/\/image.tmdb.org\/t\/p\/w185\/46ef2FOF35ieIFM14A8F8nch85t.jpg",
          "Leslie Schofield": "https:\/\/image.tmdb.org\/t\/p\/w185\/r1WQsrbi1XkbfpORgrWTDNGQCKD.jpg",
          "David Ankrum": "https:\/\/image.tmdb.org\/t\/p\/w185\/vo6JMA38exMSSbyQ3K0YCBwBrWT.jpg",
          "James Earl Jones": "https:\/\/image.tmdb.org\/t\/p\/w185\/pxC7jiRTHArPldgkqSneXRsrRJ9.jpg",
          "Burnell Tucker": "https:\/\/image.tmdb.org\/t\/p\/w185\/kRMCT2aPlZO5Cl5404mRyHEQBt6.jpg",
          "Anthony Lang": "https:\/\/image.tmdb.org\/t\/p\/w185\/xdrvGrXLIVw65PTdozxHUPDRgFQ.jpg",
          "Harrison Ford": "https:\/\/image.tmdb.org\/t\/p\/w185\/7CcoVFTogQgex2kJkXKMe8qHZrC.jpg",
          "Peter Mayhew": "https:\/\/image.tmdb.org\/t\/p\/w185\/hAavH3DKfzia7b3CTFkHd8HLgCz.jpg",
          "Frank Henson": "https:\/\/image.tmdb.org\/t\/p\/w185\/dFYRMa53HVxrHahfMMgutssxsMP.jpg",
          "John Chapman": "https:\/\/image.tmdb.org\/t\/p\/w185\/oqqR2ylj8CyjlIaSizHlhQlZ1PV.jpg",
          "David Prowse": "https:\/\/image.tmdb.org\/t\/p\/w185\/a2RoHYMSiRqV6hXL6Z5CXtNyDkt.jpg",
          "Denis Lawson": "https:\/\/image.tmdb.org\/t\/p\/w185\/78Yl1gcn5TpS6WsZ1tjwdX8j7Vd.jpg",
          "Alec Guinness": "https:\/\/image.tmdb.org\/t\/p\/w185\/nv3ppxgUQJytFGXZNde4f9ZlshB.jpg",
          "Paul Blake": "https:\/\/image.tmdb.org\/t\/p\/w185\/WSO4C7YdURE1thtj2MPkWSKD6o.jpg",
          "Mark Hamill": "https:\/\/image.tmdb.org\/t\/p\/w185\/zUXHs0t0rhRNg7rD1pQm09KXAKP.jpg"
        },
        "backdrop_original": [
          "https:\/\/image.tmdb.org\/t\/p\/original\/c4zJK1mowcps3wvdrm31knxhur2.jpg"
        ],
        "clear_art": [
          
        ],
        "logo": [
          
        ],
        "banner": [
          
        ],
        "backdrop": [
          "https:\/\/image.tmdb.org\/t\/p\/w1280\/c4zJK1mowcps3wvdrm31knxhur2.jpg"
        ],
        "extra_fanart": [
          
        ]
      },
      "actors": [
        "Mark Hamill",
        "Harrison Ford",
        "Carrie Fisher",
        "Peter Cushing"
      ],
      "writers": [
        "George Lucas"
      ],
      "runtime": 121,
      "type": "movie",
      "released": "1977-05-25"
    },
    "_t": "media",
    "releases": [
      
    ],
    "title": "Star Wars",
    "_rev": "00010da1",
    "profile_id": "d8d65f822df746bbabcf83ff491ea2b2",
    "_id": "52a3baa0de554b3b9305f729d3882c36",
    "category_id": null,
    "type": "movie",
    "identifiers": {
      "imdb": "tt0076759"
    }
  },
  "success": true
}
```

For available methods reference included [CouchPotato::class](src/CouchPotato.php)
