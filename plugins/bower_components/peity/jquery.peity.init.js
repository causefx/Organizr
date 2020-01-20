        //pie
            $("span.pie").peity("pie",{
                width: 50,
                height: 50 
            });
        
        //donut

          $("span.donut").peity("donut",{
                width: 50,
                height: 50 
            });

         // line
         $('.peity-line').each(function() {
            $(this).peity("line", $(this).data());
         });

         // bar
          $('.peity-bar').each(function() {
            $(this).peity("bar", $(this).data());
         });
         
   