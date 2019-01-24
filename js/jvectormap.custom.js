

$(':checkbox:checked').prop('checked',false);
var mapData = {
                "US": 298,
                "SA": 200,
                "AU": 760,
                "IN": 200,
                "GB": 120,
            };


jQuery('#world-map-markers').vectorMap(
{
    map: 'world_mill_en',
    backgroundColor: '#353c48',
    borderColor: '#fff',
    borderOpacity: 0.25,
    borderWidth: 0,
    color: '#5b6474',
    regionStyle : {
        initial : {
          fill : '#5b6474'
        }
      },

    markerStyle: {
      initial: {
                    r: 7,
                    'fill': '#fff',
                    'fill-opacity':1,
                    'stroke': '#000',
                    'stroke-width' : 2,
                    'stroke-opacity': 0.4
                },
                },
   
    markers : [{
        latLng : [21.00, 78.00],
        name : 'INDIA : 350'
      
      },
      {
        latLng : [-33.00, 151.00],
        name : 'Australia : 250'
        
      },
      {
        latLng : [36.77, -119.41],
        name : 'USA : 250'
      
      },
      {
        latLng : [55.37, -3.41],
        name : 'UK   : 250'
      
      },
      {
        latLng : [25.20, 55.27],
        name : 'UAE : 250'
      
      }],
      series: {
                    regions: [{
                        values: mapData,
                        scale: ["#232832", "#202733"],
                        normalizeFunction: 'polynomial'
                    }]
                },
    hoverOpacity: null,
    normalizeFunction: 'linear',
    zoomOnScroll: false,
    scaleColors: ['#b6d6ff', '#005ace'],
    selectedColor: '#c9dfaf',
    selectedRegions: [],
    enableZoom: false,
    hoverColor: '#fff',
    
    
});


$('#india').vectorMap({
            map : 'in_mill',
            enableZoom: false,
            backgroundColor : 'transparent',
            regionStyle : {
                initial : {
                    fill : '#13dafe'
                }
            }
        });


        $('#usa').vectorMap({
            map : 'us_aea_en',
            backgroundColor : 'transparent',
            regionStyle : {
                initial : {
                    fill : '#99d683'
                }
            }
        });
        
       
        
        $('#australia').vectorMap({
            map : 'au_mill',
            backgroundColor : 'transparent',
            regionStyle : {
                initial : {
                    fill : '#ffca4a'
                }
            }
        });
        
         $('#uk').vectorMap({
            map : 'uk_mill_en',
            backgroundColor : 'transparent',
            regionStyle : {
                initial : {
                    fill : '#6164c1'
                }
            }
        });

       
        