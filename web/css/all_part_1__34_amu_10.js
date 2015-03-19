var amu={
  _titre:"Amu Symfony Framework - javascript tools",
  _auteur:"AMU - DOSI <michel.ubeda@univ-amu.fr>",
  _version : "v1.37 04/03/2015 13:00 fixedHeader sur datatable fonctionnel",
  _lastmaj : "04/03/2015 13:00",
  _tipsclass:"cluetip",
  _picAdd:"",
  _picRemove:"",
  _picClose:"",
  _picCopy:"",
  _picError:"",
  _picWait:"",
  _clipboardSWF:"",
  clip:null,
  
  _dataTableFrTraduc:{ // version 1.10.1
        processing:     "Traitement en cours...",
        search:         "Rechercher&nbsp;:",
        lengthMenu:     "Afficher _MENU_ &eacute;l&eacute;ments",
        info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
        infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
        infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
        infoPostFix:    "",
        loadingRecords: "Chargement en cours...",
        zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
        emptyTable:     "Aucune donnée disponible dans le tableau",
        paginate: {
            first:      "Premier",
            previous:   "Pr&eacute;c&eacute;dent",
            next:       "Suivant",
            last:       "Dernier"
        },
        aria: {
            sortAscending:  ": activer pour trier la colonne par ordre croissant",
            sortDescending: ": activer pour trier la colonne par ordre décroissant"
        }
    },
    
  setResPicAdd:function(url){ amu._picAdd=url; },
  setResPicRemove:function(url){ amu._picRemove=url; },
  setClipboardSWF:function(url){ amu._clipboardSWF=url; },
  setResPicWait:function(url){ amu._picWait=url; },
  setResPicError:function(url){ amu._picError=url; },
  setResPicCopy:function(url){ amu._picCopy=url; },
  setResPicClose:function(url){ amu._picClose=url; },
  
  /**
   * Renvoi l'expression JSON d'un objet 
   * @param {object} obj
   * @returns {String}
   */
  //toSource:function(obj){ return('{ '+decodeURIComponent($.param(obj).toString().replace(/&/g,'","').replace(/=/g,'="') )+'" }'); },
  toSource:function(obj,debug){
    if(debug==null) debug=false;
    var s="";
    if(obj!==null)
    $.map( obj, function( value, key ) {
      var lv=((  typeof(key) === 'string' )?'"'+key+'"=':key+'=');
      if(value!==null)
      switch( typeof(value) ){
        case "boolean": lv+= value.toString(); break; // lv+= ((value==1)?'true':'false');
        case "number":lv+= value; break;
        case "string": lv+= '"'+value+'"'; break;
        case "object": lv+= amu.toSource(value); /*if(debug){ alert("object detected... on "+key+"=>"+value);} */ break;
        case "null": lv+='null'; break;
        
        case "undefined": 
        case "function": break;
        default : alert("amu.toSource() 'typeof(value)'= inconnu [key="+key+"] : "+typeof(value)); break;
      }
      s += ((s!="")?",":"") + lv;
    }); 
    return("{ "+s+" }");
  },
  
  initFormStyle:function(){
    //$(".amuForms table").addClass("tablesorter");
    //$('form label').wrap('<div class="decal" style="padding-top:15px;vertical-align:bottom;" />');
    //$('input[id$="_color"]').addClass("spectrum");
    $('input[readonly]').addClass("ui-state-disabled");
    $('label').css('color','blue');
    $('.required').css('color','red');  //cf symfony-form.css
    try { $('.datetime').datepicker(); } catch(e) { }
    $('td,option,select,label').addClass("TabInfos");
//    $('label').addClass("TabInfosCB");
    
    // FormTablesorter  amuForms table
    try {
      $(".FormTablesorter").tablesorter({
          widgets: ['zebra'],
          headers: { 0: { sorter:false }, 1: { sorter: false }}
          });
    } catch(ex){} 
    try { $(".tablesorterV2").tablesorter({ widgets: ['zebra'] }); } catch(ex){}     
    
    $('<br>').insertBefore(".amuForms input[type='radio']");
    $('<br>').insertBefore(".amuForms input[type='checkbox']");
  },
  
  initJQueryControls:function(){
    /** @description Permet de rajouter dans les champs input text une infos d'aide à la saisie (via attr 'hints') en gris clair
    * @dependances css : .text-label { color: #cdcdcd; font-weight: bold; }
    */
   $("table.zebra tbody tr:odd").css("backgroundColor","white");
   $("table.zebra tbody tr:even").css("backgroundColor","#F0F8FF");
   $("table thead th").addClass("TabInfos");
   
    $('input.fieldinfos').each(function(i){
      var id=$(this).attr('id');
      if(id!=""){
        var infos=$(this).attr('hints');
        if(infos!=""){
          var idH="_hints_text_"+id+i;
          var hint='<div id="'+idH+'" class="text-hints" style="position:relative;z-index:1;">'+infos+'</div>';
          $(this).removeClass("fieldinfos").after(hint);
          $('#'+idH).position({my:"left+5 center-20",at:"left+2 center"+((jQuery.browser.mozilla)?"+25":""),of: "#"+id,collision: "fit"+((jQuery.browser.mozilla)?" flip":"") });
          $(this).focus(function(){ if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $(this).blur(function(){  if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $(this).keyup(function(){ if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $('#'+idH).click(function(){ $('#'+id).focus(); })
          var wH=$('#'+idH).css("width").toString().replace('px','');
          var w=$(this).css("width").toString().replace('px','');
          if(wH>w){
            $('#'+idH).css("width", (w-15) +"px").addClass('jqtip').attr('title','<small>'+hint+'</small>').css('overflow','hidden').html(infos.toString().slice(0,(w*0.245))+'...');
          }
        }
      }
    });
    
    $(".FixedHeader_Cloned").remove();

    try { $( ".accordion_debug" ).accordion({ collapsible:true, active:false }); } catch(ex){} 
    try { $( ".accordion" ).accordion(); } catch(ex){} 
    try { $(".tablesorter,.tablesorterV2,.tablesorter6").tablesorter({ widgets: ['zebra'], sortList:[[0,0]] }).sort([0,0]); } catch(ex){} 
    try {
     
      $('.datatable,.dataTable').addClass('ui-widget-content TabInfos');    
      $('.datatable,.dataTable').each(function(){
        var opt={};
        var f=(($(this).attr("filterTxt")!=="")?$(this).attr("filterTxt"):"");
        var ft=(($(this).attr("filter")!=="")?$(this).attr("filter"):"");
        var fh=(($(this).attr("fixedHeader")!==undefined)?($(this).attr("fixedHeader")==="true"):false);
        opt={
          "language": amu._dataTableFrTraduc,
          "destroy": true,    
          "retrieve": true,
          "jQueryUI": true,
          "aoColumnDefs": [
              { aTargets: [ 'no-searchable' ], bSearchable: false },
              { aTargets: [ 'no-sortable' ], bSortable: false },
            ],
          "drawCallback": function() { 
            /*optimisation des marges/lignes dans les tablesorter/datatable.. */
            $("table thead th").css("padding","3px");
            $("table tfoot th,table td").css("padding","3px 3px").css("vertical-align","middle");
            $('a.fg-button').css("padding","2px");
            try{ amu.initTips();}catch(e){}
          }
        };
        if(ft!==""){
          opt["pagingType"]="full_numbers";
          opt["displayLength"]=ft;
          opt["lengthMenu"]=[[10,25,50,100,200,500,-1], [10,25,50,100,200,500,"Tous"]];
        }
        //
        // if(fh){ opt["paginate"]=false; }
        //
        var dt=$(this).dataTable(opt);
        /* FixedHeader */
        if(fh){ try{ new $.fn.dataTable.FixedHeader( dt ); }catch(e){} }
        
        /* obsolète resolv bug ancienne version; à partir 1.10.4 => OK MAIS conservé pour compatibilité versions antérieures */      
        if(f!==undefined) { dt.fnFilter(f); /* .fnDraw();*/ }
        //
      });
    } catch(ex){} 
    
    $(".FixedHeader_Cloned").css("z-index",""); // resolv bug visuel ( FixedHeader_Cloned doit être en dessous des UI Dialog de JQuery (104=>100)

    // permet d'effacer le filtre du datatable sur click dans le champs "filtre"
    $('input[aria-controls]').click(function(){ $(this).val("").keyup(); });
    $('select[aria-controls]').addClass("TabInfos").css("color","");
    //resolv bug dataTable first view paging = -1
    $('select[aria-controls]').change();
    $('.dataTables_length,.dataTables_filter,.dataTables_paginate,.dataTables_info,.fg-toolbar').addClass("TabInfos");
    $('.dataTables_length label,.dataTables_filter label,.dataTables_info').css('color',"white");
    $('a.fg-button').css("padding","2px");   
   
    //resolv bug jquery tips fantome sur click
    $('.jqtip').click(function(){ try{ $(this).tooltip("close"); }catch(ex){} });
    $('li.menuAMU a').click(function(){ $(this).blur(); });

    //all contenu de datatable => 8px
    $(".fg-toolbar").css('font-size',"8px").css("margin-right","-2px");

    /*optimisation des marges/lignes dans les tablesorter/datatable.. */
    $("table thead th").css("padding","3px");
    $("table tfoot th,table td").css("padding","3px 3px").css("vertical-align","middle");

    try { 
      $(".select2").each(function(){
        var mi=(($(this).attr("minimumInputLength")!=="")?$(this).attr("minimumInputLength"):0);
        var mx=(($(this).attr("maximumSelectionSize")!=="")?$(this).attr("maximumSelectionSize"):0);
        var p= (($(this).attr("placeholder")!=="")?$(this).attr("placeholder"):"");
        try { 
              $(this).select2({
                width: "element",
                minimumInputLength: mi,
                maximumSelectionSize:mx,
                placeholder:p,
              });
        } catch(ex){} 
      });              
      $("input.select2-input").click(function(){ $(this).val("").keyup(); })
    } catch(ex){} 
  
    try { $(".select-c2").chosen({placeholder_text_single:"Veuillez choisir une option",placeholder_text_multiple:"Veuillez choisir une option ou plus..." ,no_results_text:"Aucun résultat trouvé...",width:"element",disable_search_threshold:10}); } catch(ex){} //.selectBoxIt();
    try { $(".select-c1").chosen({placeholder_text_single:"Veuillez choisir une option",placeholder_text_multiple:"Veuillez choisir une option ou plus...",no_results_text:"Aucun résultat trouvé...",width:"element",disable_search:true}); } catch(ex){} //.selectBoxIt();
    //$(".select-c1").css("height","26px");
    //try { $(".chosen-select").chosen({no_results_text:"Aucun résultat trouvé..."}); } catch(ex){} 
    //$("li.active-result").wrapInner( "<nobr></nobr>");
    //$("div.chzn-search input").click(function(){ $(this).val("").keyup(); });
    
    try { $(".jqmenu").menu({ icons:{ submenu: "ui-icon-circle-triangle-e" },role:"menu" }); } catch(ex){} 
    try { amu.initTips(); }catch(ex){} 
    try { amu.initColorPicker(); }catch(ex){}
    try { amu.initDatepicker(); }catch(ex){} 
    try { amu.initZeroClipboard(); }catch(ex){} 
    
    //amu.initInputHints();
    /*
    $(".editor").each(function(){ 
      var idCK=$(this).attr("id");
      if(idCK!=""){ 
        $(this).hide();
        $(this).after('<div id="editor_'+idCK+'" name="editor_'+idCK+'" ></div>')
        CKEDITOR.replace( 'editor_'+idCK );
        $('#editor_'+idCK).show();
        //$('#ckedtidor_'+idCK ).change(function(){ $('#'+idCK).val($('#ckedtidor_'+idCK).html()); })
        $('#editor_'+idCK ).keyup(function(){ $('#'+idCK).val($('#editor_'+idCK).html()); })
      }
   });
   */
    //$( 'textarea.ckeditor' ).ckeditor();
    // oblige l'envoi des variables en POST
    $.ajaxSetup({type:"post"});
    
    // autoUpdate CKEditors
    amu.majCKEditors();

    //ZeroClipboard.setDefaults( { moviePath: amu._clipboardSWF } );

    // resolv IE 9 bug on A linked content IMG => border
    // $('a img').css("border","0px"); => ie.css
    
  },
  
  /** Mise à jour d'un ctrl CKEDITOR @param {string} id */
  majCKEditor:function(id){ if(id!==""){ try { CKEDITOR.instances[id].updateElement(); }catch(e){}} },
  
  /** Mise à jour de TOUS les ctrl de type CKEDITOR @param {string} id */
  majCKEditors:function(){
    if (typeof CKEDITOR !== "undefined") {
      for ( i in CKEDITOR.instances ){
        CKEDITOR.instances[i].on( 'change', function(){ this.updateElement(); $('#'+this.name).val( this.getData() ); });
      }
    }
  },
  
  initZeroClipboard:function(){
    
//    try {
      ZeroClipboard.config( { swfPath: amu._clipboardSWF } );
      var client = new ZeroClipboard($(".copy-button")); 
     
      client.on( 'ready', function(event) {
        // console.log( 'movie is loaded' );

        client.on( 'copy', function(event) {
          if(event.target.value !== undefined ){
            event.clipboardData.setData('text/plain', event.target.value);
          }else{
            event.clipboardData.setData('text/plain', event.target.innerHTML);
          }
          
        } );
         
        client.on( 'aftercopy', function(event) {
          alert("Élément Copié...")
          //console.log('Copied text to clipboard: ' + event.data['text/plain']);
        } );
        
      } );

      client.on( 'error', function(event) {
        // console.log( 'ZeroClipboard error of type "' + event.name + '": ' + event.message );
        ZeroClipboard.destroy();
      } );
      
      
      
      
      // RESOLV BUG ui.dialog + ZeroClipboard
      if (/MSIE|Trident/.test(window.navigator.userAgent)) {
        (function($) {
          var zcClass = ZeroClipboard.config('containerClass');
          $.widget( 'ui.dialog', $.ui.dialog, {
            _allowInteraction: function( event ) {
              return this._super(event) || $( event.target ).closest( zcClass ).length;
            }
          } );
        })(window.jQuery);
      }
      
//    } catch(ex){} 
    
  },
  
  /** @description Permet de rajouter une information d'aide à la saisie en 'overlay'/en gris clair, pour tous les champs input text qui ont un attribut 'hints'
    * @dependances css : .text-label
    */
  initInputHints:function(){
    $('input[hints]').each(function(i){
      var id=$(this).attr('id');
      if(id!=""){
        var infos=$(this).attr('hints');
        if(infos!=""){
          var idH="_hints_text_"+id;
          var hint='<span id="'+idH+'" class="text-hints" >'+infos+'</span>';
          $(this).removeAttr("hints").after(hint);
          //$('#'+idH).css('width',+(w-15)+'px;');
          $('#'+idH).position({my:"left+5 center",at:"left+2 left"+((jQuery.browser.mozilla)?"":""),of: "#"+id,collision: "fit"+((jQuery.browser.mozilla)?" flip":"") });
          $(this).focus(function(){ if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $(this).blur(function(){  if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $(this).keyup(function(){ if($(this).val()!=""){$('#'+idH).hide();}else{$('#'+idH).show();}  });
          $('#'+idH).click(function(){ $('#'+id).focus(); })
//          var wH=0;


          var s=($(this).attr('size')/0.24); //
          var l=(infos.toString().length/0.24);

          var w=$(this).width(); //
          var w2= $(this).css("width"); //.toString().replace('px','');
          
          var wh=$('#'+idH).width(); //
          var wh2= $('#'+idH).css("width"); //.toString().replace('px','');
          
          //var lH=((w-15)*0.24);
          alert("s="+s+"\nl="+l+" \n w="+w+" \n w2="+w2+" \n\n wh="+wh+"\n wh2="+wh2);
          //if($('#'+idH)) wH=$('#'+idH).css("width").toString().replace('px','');
          if(l>s){
            $('#'+idH).css("width", (w-15) +"px").addClass('jqtip').attr('title','<small>'+hint+'</small>').html(infos.toString().slice(0,((w-15)*0.24))+'...');
          }
        }
      }
    });
    
  },
          
  addDatetimeViewer:function(ctrl){
    var id = $(ctrl).attr('id');
    try { $("#dtViewer_" + id).remove(); }catch (e){}
    var $div = $('<div id="dtViewer_' + id + '" class="ui-helper-clearfix" ></div>');
    $div.insertBefore($(ctrl));
    $("#dtViewer_" + id).datepicker({ setDate:$(ctrl).attr('value')}).unbind('change');
  },
          
  initDatepicker:function(){
    $(".datetimeRO").each(function(){
      var id = $(this).attr('id');
      try { $("#dtViewer_" + id).remove(); }catch (e){}
      var $div = $('<div id="dtViewer_' + id + '" class="ui-helper-clearfix" ></div>');
      $div.insertBefore($(this));
      $("#dtViewer_" + id).datepicker({ setDate:$(this).attr('value')});
      $(this).bind('change',function(){ $("#dtViewer_"+$(this).attr('id') ).datepicker( "setDate",$(this).attr('value') ); });
    });
    $(".datepicker").datepicker();
    $(".datepicker2").datepicker({ "setDate":"10/12/2012" });
    $(".datepickerYM").datepicker({changeYear: true,changeMonth:true});  
    $(".datetimepickerWT").datetimepicker({dateFormat:"dd/mm/yy",timeFormat: "HH:mm",minuteGrid:10,hourGrid:2,hourMin:8,hourMax:18});
    $(".datetimepicker").datetimepicker({dateFormat:"dd/mm/yy",timeFormat: "HH:mm",minuteGrid:10,hourGrid:4});
    $(".timepicker").timepicker({timeFormat: "HH:mm",minuteGrid:5,hourGrid:2});
    // resolv bug css calendar 
//     $(".ui-tpicker-grid-label").css("font-size","5px");
//     $(".ui-slider-handle").css("width","1.em").css("height","1.em");
},
  
  initColorPicker:function(){
     $(".spectrum").spectrum({
      //color: '#123456', // si on veux forcer à une valeur...
      flat: false, // si on veux le ctrl "inline"
      showButtons: true,
      showInput: true,
      showInitial: true,
      showAlpha: false,
      //localStorageKey: string,
      showPalette: true,
      showPaletteOnly: false,
      showSelectionPalette: true, // add palette selected colors
      clickoutFiresChange: true,
      cancelText: "Annuler",
      chooseText: "Choisir",
      //className: string,
      preferredFormat: "hex", // hex,hsl,rgb,name
      //maxSelectionSize: int,
      palette: [  ["000000","666666","cdcdcd","e7e7e7","ececec","eeeeee","ffffff"], // amu gray
                  ["0071b9","22bbea","fbba00","ff8800","970000","7cb61f","333333"], // amu col1
                  ["ff8800","f1da10","94dc23","22bbea","0071b9","5943ff","b243ff"], // amu col2
                  ["FF0000","FFFF00","00FF00","FFA500","00FFFF","FF00FF","0000FF"], // fluo
                  ["FFC1C1","FFFACD","E0EEE0","FFDAB9","BBFFFF","DDA0DD","6495ed"]  // pales
      ]
      //selectionPalette: [string]
      //move: function(color) { $('#changeOnMoveLabel').html(color.toHexString()); },
      //change: function(color) { $('#tipTester').css('backgroundColor',color.toHexString()); },
    });
  },
  initTips:function(defWidth) {
    // jQuery Tips    
    try{
      $(".jqtip_arrow").tooltip({
          position: {
              my: "center bottom-20",
              at: "center top",
              using: function( position, feedback ) {
                  $( this ).css( position );
                  $( "<div>" ).addClass( "arrow" ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this );
              }
          }
      });
      $(".jqtip").tooltip({
          position: {
              my: "center bottom-20",
              at: "center top",
              using: function( position, feedback ) {
                  $( this ).css( position );
                  $( "<div>" ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this );
              }
          }
      });
      /* 
       * En attente de validation....
       * 
      $('.jqtip_ui').each(function(){
        var h=$(this).attr('htitre'); var ht=((h!=="")?parseInt(h):40);
        var w=$(this).attr('wtip');   var wtip=((w!=="")?parseInt(w):300);
        var t=$(this).attr('titre');  var titre=((t!==undefined)?t:"Informations...");
        var c=$(this).attr('css');    var css=((c!==undefined)?c:"ui-tooltip-blue");
        var h=$(this).attr('cssH');   var cssh=((h!==undefined)?h:"");
        var o=$(this).attr('opacity');var opacity=((o!==undefined)?o:"1");
        var sticky=($(this).attr('sticky')==="true"); 
        var cssA=""; var closeBt=""; var bc=""; var removeClass="";
        switch($(this).attr('arrow')){
          case "false":break;
          case "LR": cssA="arrowLR"; break;
          case "LRC": cssA="arrowLRC"; break;
          case "": cssA="arrow"; break;
        }
        if(sticky){
          var cbt=$(this).attr('closeBt');
          id="_sticky_tip_"+Math.random().toString().replace(".",""); 
          $(this).attr("id",id);
          closeBt="&nbsp;<img class='ui-button"+((cbt==="ui")?" ui-icon ui-icon-close'":"' src='"+amu._picRemove+"' ")+" title='Cliquer ici pour fermer...' align='right' onclick='$(\"#"+id+"\").tooltip(\"close\");'/>";
          //$(this).draggable();
        }
        var style="vertical-align:middle;margin:-10px;padding:3px;min-height:"+ht+"px;cursor:pointer;";
        var cssHead="ui-widget ui-widget-header ui-corner-top";
        var cssContent=((css==="ui-tooltip-blue")?"":css);
        
        if(css==="ui-tooltip-blue"){
          bc="#4297D7"; // bc="#C5DBEC;";
          switch(wtip){ case 300: case 600: case 900: css+="-"+wtip; break; }
        }else{
          removeClass="ui-widget-content";
          if(css.toString().search(/ui-state/)===false){
            cssContent=css;
            cssHead=css;
            //css="";
          }else{
            cssContent="";
          }
        }
        if(cssh!==""){
          cssHead=cssh;
          //style="vertical-align:middle;padding:3px;min-height:"+ht+"px;cursor:pointer;";
        }
        
        $(this).tooltip({
          tooltipClass: css,
          content: "<div class='"+cssHead+"' style='"+style+"' ><b>"+titre+closeBt+"</b></div></br><div class='"+cssContent+"'>"+$(this).attr('title')+"</div>",
          position: {
            my: (cssA==="arrow")?"center bottom-10":"left+55 " + ((cssA==="arrowLR")?"top":"center")+"-10",
            at:  (cssA==="arrow")?"center top":"center right ",
            using: function( position, feedback ) {
              $( this ).css( position );
              $( "<div>" ).addClass( cssA ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this )   
                .parent().removeClass(removeClass).css("opacity",opacity).css("border-color",bc)
                .on('mouseout',function(){ $(this).css("z-index",100)})
                .on('mouseover',function(){ $(this).css("z-index",10000)})
                ;
              }
            }
         });

       }).on('mouseout focusout', function(event) { if($(this).attr('sticky')==="true"){ event.stopImmediatePropagation(); } });
*/
    }catch(e){ }
    // clueTip (plug jQuery)
    if(!defWidth) defWidth=275;
    try{
      $('.cluetip').each(function(i){
      var w=$(this).attr('wtip');
      defWidth=((w!=="")?parseInt(w):defWidth);
      $(this).cluetip({
        cluezIndex:197,
        splitTitle: '|',
        width: defWidth,
        cursor:'pointer',
        //hoverClass: 'highlight', 
        cluetipClass: 'rounded',
        arrows:true,
        dropShadow: true,
        hoverIntent: true,
        sticky:false,
        mouseOutClose: true,
        closePosition: 'title',
        closeText: '<img src="'+amu._picClose+'" alt="fermer" />&nbsp;Fermer...'
        }).removeClass('cluetip');
      });
    }catch(e){}
  },
  
  /*@description Renvoi un chiffre aléatoire compris entre 'from' et 'to' (optionnel :'len' force le remplissant du résultat avec des zéros pour atteindre la longueur de chaine 'len')
   *@param {int} from le chiffre de debut
   *@param {int} to le chiffre de fin
   *@param {int} len OPTIONEL=formattage en nombre de chiffre (par defaut=10 => 0000000001 )
   *@param {array/int} exceptValues OPTIONEL=valeur(s) interdite(s) : [on boucle et regénère "rnd" tant qu'il fait parties des "exceptValues"...]*/
  rnd:function(from,to,len,exceptValues){
    if(exceptValues===null) exceptValues=false;
    len=((!len)?10:len);
    from=((!from)?1:from);
    to=((!to)?9999999999:to);
    var rnd=Math.random();
    rnd=eval(rnd*Math.pow(10,len));
    rnd=Math.round(rnd);
    while((rnd<from)||(rnd>to)){
      if(rnd<from)rnd=rnd*2;
      if(rnd>to)rnd=eval(rnd-(rnd/3));
      rnd=Math.round(rnd);
    }
    // Boucle si rnd [= exceptValues
    if(exceptValues!==false){
      if($.isArray(exceptValues)){
        for(var i=0;i<exceptValues.length;i++){
          if(rnd===exceptValues[i]){
            rnd=amu.rnd(from,to,len);
            i=0;
          }
        }
      }
      else{// oneValue
        if(rnd===exceptValues){
          rnd=amu.rnd(from,to,len);
        }
      }
    }
    return(rnd);
  },
  /*@description Copy la valeur d'un elements HTML dans ke presse papier @param {string} id identifiant de l'element*/
  copyObjVal2Clipboard:function(id) {
    if(amu.clip===null){
      amu.clip = new ZeroClipboard({ moviePath: amu._clipboardSWF } );
    }
    amu.clip.setText($("#"+id).val());
    return true;
  },

	/*@description Add CSV Value (text) with sep (,) to id 
    @param {string} id description @param {string} Val Valeur de l'élément à ajouter @param {string} sep le séparateur  */
	av:function(id,Val,sep){if(sep==null) sep=','; var v=$('#'+id).val(); if(v!=null)if(v!=""){ var ar=v.split(sep); if(ar.length>0){ /* test ispresent ?*/ if(amu.iv(id,Val,sep)==false){ $('#'+id).val(v+sep+Val); } }  }else{/*no elements*/ $('#'+id).val(Val); } },
	/*@description Is into Value text listing(csv sep=,) of Ctrl ?
    @param {string} id description @param {string} Val Valeur de l'élément à ajouter @param {string} sep le séparateur  */
	iv:function(id,Val,sep){if(sep==null) sep=',';var p=false;var v=$('#'+id).val()+sep; var ar=v.split(sep);for(var i=0;i<ar.length;i++){if(ar[i]!="")if(ar[i]===Val){p=true; break;}} return(p);},
	/*@description Remove Value form text listing(csv sep=,) of id 
    @param {string} id description @param {string} Val Valeur de l'élément à ajouter @param {string} sep le séparateur  */
  rv:function(id,Val,sep){if(sep==null) sep=','; if(amu.iv(id,Val,sep)==true){ var nv=Array(); var v=$('#'+id).val()+sep; var ar=v.split(sep);var j=0;for(var i=0;i<ar.length;i++){ if(ar[i]!="")if(ar[i]!==Val){ nv[j]=ar[i]; j++; } } $('#'+id).val(nv); } },
  
  addListing:function(id,label,urlListing,dlg,valid,cancel,tiny,limit,debug){
    var tinyFontSize="smaller";
    if(dlg==null) dlg=false;
    if(tiny==null) tiny=false;
    if(debug==null) debug=false;
    if(limit==null) limit=0;
    $('#'+id).before('<div title="'+label+'" id="ctrl'+id+'" class="ui-widget-content ui-corner-all" style="padding:'+(tiny?2:5)+'px;'+(tiny?'font-size:'+tinyFontSize+';':'')+(dlg?'display:none;':'')+'" >'+((label!=="")?label+'</br>':"")+'<nobr><select class="TabInfos" style="font-size:'+(tiny?tinyFontSize:10)+';" id="_AjaxList'+id+'" onmouseover=$("#_AjaxListIsPresent'+id+'").hide(); ></select>&nbsp;'+
      (tiny?'<span class="ui-button ui-icon ui-icon-plus ui-corner-all jqtip"  style="margin-top:4px;" title="<small>Ajouter l&apos;&eacute;l&eacute;ment s&eacute;lectionn&eacute; dans la liste ci-dessous...</small>" onclick="amu.optionAdd(\''+id+'\',null,'+tiny+','+limit+')" ></span><nobr>' +
            '<div id="_AjaxListIsPresent'+id+'" style="padding:2px;display:none;font-size:'+tinyFontSize+';" class="ui-state-error ui-corner-all" onmouseover=$(this).hide(); ><span class="ui-icon ui-icon-alert ui-corner-all"></span>&nbsp;Cet &eacute;l&eacute;ment est d&eacute;j&agrave; pr&eacute;sent dans la s&eacute;lection...</div>'
        :'<img align="absmiddle" class="ui-state-default ui-corner-all ui-button jqtip" style="padding:2px;" title="<small>Ajouter l&apos;&eacute;l&eacute;ment s&eacute;lectionn&eacute; dans la liste ci-dessous...</small>" onclick="amu.optionAdd(\''+id+'\',null,'+tiny+','+limit+')" src="'+amu._picAdd+'" /><nobr>' +
         '<br><br><p id="_AjaxListIsPresent'+id+'" style="padding:5px;display:none;font-size:small;" class="ui-state-error ui-corner-all" onmouseover=$(this).hide(); ><img align="absmiddle" src="'+amu._picError+'" />&nbsp;Cet &eacute;l&eacute;ment est d&eacute;j&agrave; pr&eacute;sent dans la s&eacute;lection...</p>')+
    '<div id="choices'+id+'" style="padding:'+(tiny?1:10)+'px;" ></div></div>');
    if(dlg){
      $("#ctrl"+id).dialog({ 
        //modal:true,
        buttons: [
          { text: "Ok", click: function() { $(this).dialog("close"); if(valid!=null){ valid();  } $("#ctrl"+id).remove();  } },
          { text: "Annuler", click: function() { $(this).dialog("close"); if(cancel!=null){ cancel(); } $("#ctrl"+id).remove(); } }
        ] }); // "option":{"modal":true}).open();
    }
    $('#'+id).attr('readonly','readonly');
    $('#'+id).css('width','935px').css('font-size',(tiny?6:10)+"px");
    if(!debug) $('#'+id).hide();
      amu.ajx2('_AjaxList'+id,urlListing);
      var curVal=$('#'+id).val();
      if(curVal!=""){
        var arValues=(curVal+",").split(',');
        for(var i=0;i<arValues.length;i++){
          amu.optionAdd(id,arValues[i],tiny);
        }
      }
    amu.initTips();
    if(tiny) $("#_AjaxList"+id+" option").css("font-size",tinyFontSize);
  },

  optionAdd:function(id,newValue,tiny,limit){
    var tinyFontSize="smaller";
    if(tiny==null) tiny=false;
    if(limit==null) limit=0;
    var majIHM=false;
    if((newValue==null)||(newValue==undefined)){
      newValue=$('#_AjaxList'+id).val();
    } else majIHM=true;
    
    if(newValue)
    if(newValue.toString()!=""){
      var iddel="_delListItem"+id+newValue.toString().replace(/\W/g, '_');
      var divad='<div style="padding:'+(tiny?1:5)+'px;" id="'+iddel+'" ><span class="ui-state-default ui-corner-all" style="'+ (tiny?"padding:1px;font-size:"+tinyFontSize+";":"padding:2px;font-size:small;")+ '" >' +
        (tiny?'<span class="ui-button ui-icon ui-icon-minus ui-corner-all jqtip" title="<small>Cliquez ici pour enlever cet &eacute;l&eacute;ment...</small>" onclick="amu.rv(\''+id+'\',\''+newValue+'\');$(\'#'+iddel+'\').remove();$(\'#'+id+'\').change();" ></span>'
             :'<img align="absmiddle" class="ui-button jqtip" title="<small>Cliquez ici pour enlever cet &eacute;l&eacute;ment...</small>" src="'+amu._picRemove+'" onclick="amu.rv(\''+id+'\',\''+newValue+'\');$(\'#'+iddel+'\').remove();$(\'#'+id+'\').change();" / >'
        ) + '&nbsp;'+newValue+'&nbsp;</span></div>';
       
      if(majIHM){
         $('#choices'+id).append(divad);
      }
      else{
        if(amu.iv(id,newValue)==false){
          amu.av(id,newValue);
          $("#"+id).change();
          $('#choices'+id).append(divad);
          amu.initTips();
        }
        else{
          $('#_AjaxListIsPresent'+id).show();
        }
      }
     
    }
  },
 

  /**
  * @description Fonction AJAX d'ajout d'infos générique sur PLUSIEURS éléments HTMLs en même temps
  * @since 18/12/2009
 	* @param id (string) listes des id de l' élément HTML à modifier ; SI id="" => affichage boite dialogue JS ALERT()
  * @param src (string) l'adresse (url) de l'ajax à appeler
 	* @param params (array) la suite successive des paramètre nécéssaire à l'exec de la cmd Ajax 
  *  <br>[OPTION] params['tipsWith'] => permet de définir la taille de la fenêtre d'informations 'tooltips'
  *  <br>[OPTION] params['timeout'] => permet de définir le timeout (en milisecondes) d'exécution ajax (défaut: 5000)
  *  <br>[OPTION] params['async'] => permet de définir le type d' httpRequest à exécuter asynchone (async=true) ou synchrone (bloquant) (défaut: true=> async)
  *  <br>[OPTION] params['crossDomain'] => permet de définir si l'exécution ajax acceptera le multi-domaine (défaut: true)
	* @return (boolean) true/false état de l'exécution AJAX (réussis/échoué) 
  */
  ajx2:function(id,src,params){
    var raz=true,bresult=false;
    if(id)
      if(id!==""){ 
        //  décortiques options Spéciales
        if(id.substring(0,1)=="+"){
          raz=false;
          id=id.slice(1);
        }
        //
        if(document.getElementById(id)){
          // Valeur par Défauts:
          var defTipsWidth=450;
          var defTimeout=25000; // 25 sec
          var defAsync=true;
          var defCrossDomain=true;
          var defValue="";
          var firstEmpty=true;
          var counter=false; var vlen=0;
          //
          var arParams="";
//          var p=0;
          if(params){
            var arP=new Array();
            arParams=jQuery.param(params,true);
            if(arParams!=""){
              var tmpAr=arParams.split('&');
              for(var i=0;i<tmpAr.length;i++){
                var tmpAr2=tmpAr[i].split('=');
                arP[tmpAr2[0]]=tmpAr2[1];
              }
               // Récupérations des paramètres en options...
              if(arP['tipsWidth']!==undefined) defTipsWidth=arP['tipsWidth'];
              if(arP['timeout']!==undefined) defTimeout=arP['timeout'];
              if(arP['async']!==undefined) defAsync=(arP['async']);
              if(arP['crossDomain']!==undefined && (arP['crossDomain']=="false")) defCrossDomain=false;
              if(arP['value']!==undefined) defValue=arP['value'];
              if(arP['addFirstEmptyValue']!==undefined && (arP['addFirstEmptyValue']=="false")) firstEmpty=false;
              if(arP['addValuesCounter']!==undefined && (arP['addValuesCounter']=="true")) counter=true;
            }
          }
         
          //  lancement AJAX
//          
//           $('#'+id).ajaxStart(function() {
//             
//            $('#'+id).before('<p>loading</p>').addClass('wait'+id);
//
//             // $('#'+id).insertAfter(("123&nbsp;Chargement..."); //. ('img').after('&nbsp;Chargement...');
//            });
          
          $.ajax({
            type: 'POST',
            url: src,
            data: params,
            async:defAsync,
            timeout:defTimeout,
            crossDomain:defCrossDomain,
            dataType:'json',
            beforeSend: function(){ // on ajoute l'image d'attente...
              if($('#_'+id).attr('type')!='hidden'){
                 $('#'+id).after('<span class="wait'+id+'"><img align="absmiddle" src="'+amu._picWait+'" / >Chargement...</span>');
              } 
              if(counter)
              if($('#_counter'+id)) $('#_counter'+id).remove();
            },
            error:function(jqXHR, textStatus, errorThrown){ // traitement erreur
              if(textStatus!=="abort"){
                var errTxt="<font color=red><b>ERROR</b> "+textStatus+"=> on amu.ajx2(id='"+id+"', src='"+src+"', params="+ ((params!==null)?arParams:"") +")\n\t Error: =>\n"+errorThrown+"</font>";
                if(document.getElementById(id).type!=='hidden'){
                  $('<img onDblclick="$(this).remove();" class="cluetip" title="'+errTxt+'" id=_error'+id+' align="absmiddle" src='+amu._picError+' / >').insertAfter('#'+id);
                } 
                else alert(errTxt); 
              }
            },
            success:function(data, textStatus, jqXHR){ // traitement réponse JSON
              if(textStatus==="success")
                if(id==="#"){ 
                  // var SESSION...
                }
                else
                if(id!==""){ 
                  var alertMsg="";
                  var e=document.getElementById(id);
                  switch(e.type){ //$('#'+id).attr('type')
                    case "button": case "submit": case "reset": case "file":
                      break;/*aucune modif*/
                    case "radio": case "checkbox":
                      //var lbl=document.createElement('span');
                      //@todo à faire.. ajout pour chaque radio bouton un libellé, + en input hidden correspondants à tout les valeurs JSON...
                  
                      break;
                    case "select-one": case "select-multiple": case "select":
                          
                      if(raz){ /* vidange els */ 
                        while(e.options.length>0){
                          e.options[0]=null;
                        }
                        /* ajout 1ier val à vide ("") */	
                        if(firstEmpty===true){
                          var opt0=document.createElement('option');
                          opt0.text="";
                          opt0.value="";
                          opt0.label=""; 
                          try	{
                            e.add(opt0);
                          }/*IEonly*/catch(ex){
                            e.add(opt0,null);
                          }/*allOthers*/
                        }
                      }
                      var tips_active=false;
                      // Boucle d'insertions des valeurs
                      if(data!==null)
                      for(var k in data)
                      { var oneData=data[k];
                        if(oneData!==null){
                          if(oneData['alert']!='') alertMsg=oneData['alert'];
                          var val=oneData['value'];
                          var txt=val;
                          if($.trim(oneData['label'])!='')  txt=oneData['label'];
                          var opt=document.createElement('option');
                          opt.text=txt;
                          opt.label=txt;
                          opt.value=val; 
                          if(val==defValue) $(opt).attr('selected','selected');
                            
                          //                        if(typeof(oneData['tips'])!=='undefined') 
                          if($.trim(oneData['tips'])!='')  {
                            $(opt).attr('title',oneData['label']+"|"+oneData['tips']);
                            $(opt).addClass(amu._tipsclass);
                            tips_active=true;
                          }
                          //                        if(typeof(oneData['bgcolor'])!=='undefined') 
                          if($.trim(oneData['bgcolor'])!='') {
                            $(opt).css('background-color',oneData['bgcolor']);
                            $(opt).addClass( (oneData['bgcolor']).replace("#","") );
                          }
                          //                        if(typeof(oneData['class'])!='undefined') 
                          if($.trim(oneData['class'])!='') {
                            $(opt).addClass( (oneData['class']) );
                          }
                          //                        if(typeof(oneData['value2'])!='undefined') 
                          if($.trim(oneData['value2'])!='') {
                            $(opt).attr('value2',(oneData['value2']) );
                          }
                          $(opt).addClass("TabInfos");
                          // rajout de l'option...  
                          try{
                            e[e.length]=opt; /*IEonly*/
                          }	catch(ex){
                            e.add(opt,null); /* Std compliant*/
                          }
                        }
                        
                      }	
                      if(tips_active){
                          // traitement post insertions valeurs
                          amu.initTips(defTipsWidth); // définie dans base.html.twig
                          try {
                            $(".tablesorter6").tablesorter({
                              widgets: ['zebra2'],
                              sortList:[[0,0]]
                              });
                          } catch(ex){} 
                      }    
                      if(counter) vlen=$("#"+id+" > option").length-1;
                      break;

                    case "hidden": case "password":  case "text": case "textarea":
                      if(data){
                        var oneData=eval(data);              //if($('.wait'+id)) 
                        $("#"+id).val(oneData[0]['value']).change();
                      }
                      if(counter) vlen=data.length;

                      break;
                      
                    default:/*Balise HTML: A, P, DIV...*/
                      try{
                        if(data){
                          var oneData=eval(data);
                          $("#"+id).html(oneData[0]['value']).change();
                        }
                        
                      }
                      catch(er){
                        var errTxt="<font color=red><b>ERROR</b> on amu.ajx2(id='"+id+"', src='"+src+"', params="+arParams+")<br>Error: <pre>"+er.toString()+"</pre></font><hr>";
                        if($('#_'+id).attr('type')!='hidden'){
                          $('<img onDblclick="$(this).remove();" class="cluetip" title="'+errTxt+'" id=_error'+id+' align="absmiddle" src='+amu._picError+' / >').insertAfter('#'+id); 
                        } 
                        else
                          alert("ERROR on 'hidden' control =>"+errTxt);
                        er=null;
                      }
                      if(counter) vlen=data.length;
                      break;
                  
                  }	//try{ e.onchange();}catch(er){} => no need $ > event
                  
                  if(e.type!=="hidden")
                  if(counter){
                    $('#'+id).after('<span id="_counter'+id+'" style="color:gray">('+vlen+')</span>');
                  }
                  
                  // on supprime l'image d'attente...
                  if($('#_wait'+id)) $('#_wait'+id).remove();
                  $('.wait'+id).remove();

                } // fin if id!==""
                else{
                  alert("RESULT amu.ajx2(id='"+id+"', src='"+src+"', params="+params+")÷\n\t  Data =>\n"+data);
                }
              
              if($.trim(alertMsg)!=""){
                $('#'+id).after('<img align="absmiddle" src="'+amu._picError+'" class="cluetip" title="Avertissement|'+alertMsg+'" onclick=$(this).trigger("hideCluetip");$(this).remove(); / ></span>');
                amu.initTips(800); // définie dans base.html.twig
              }
              bresult=true;
            }
          });
        }
      }
   
    return bresult;
  },
  
  cmbSelectOpt:function(id,lib){
    $("#"+id+" option").each(function(){
      if($(this).text()==lib){
        $(this).attr("selected","selected");
        $(this).change();
        return false;
      }
    });
},

	
cmbClassFilter:function(id,cls,idcpt,fireEvtChange)
{
  if(cls==null) cls='';  
  if(idcpt==null) idcpt='';
  if(cls!='')
  {
    $("#"+id+" > option").hide();
    if(cls.match(/ /)){
      var arcls=cls.split(' ');
      for(i=0;i<arcls.length;i++) $("#"+id+" > option."+arcls[i]).show();
    }
    else $("#"+id+" > option."+cls).show();
  // old style case err/no complète
  //$("#"+id+" > option").show();
  //$("#"+id+" > option:not(."+cls.replace(' ',', .')+")" ).hide();
  } 
  else $("#"+id+" > option").show();
  if(idcpt!='') $("#"+idcpt).html( ((cls!='')?$("#"+id+" > option:visible").length+"/" : "") );
  $("#"+id+" > option:first").show();
  if(fireEvtChange!=null) fireEvtChange=true;
  if(fireEvtChange==true) $("#"+id).val("").change();
},
cmbColorFilter:function(id,col,idcpt,fireEvtChange)
{
  if(col==null) col='';  
  if(idcpt==null) idcpt='';
  $("#"+id+" > option").show();
  if(col!='') $("#"+id+" > option:not(."+col.replace('#','')+")" ).hide();
  if(idcpt!='') $("#"+idcpt).html( ((col!='')?$("#"+id+" > option:visible").length+"/" : "") );
  $("#"+id+" > option:first").show();
  if(fireEvtChange!=null) fireEvtChange=true;
  if(fireEvtChange==true) $("#"+id).val("").change();
},

/** @description Init comboBox avec liste de valeur donnée (options : tips, bg col, class
	  @param {string} id l'Id de l'élément à initialiser 
	  @param {string} listValues les items à ajouter 
    @param {bool} addfirstempty option ajouter une valeur vide an début oui/non [défault:true]
	  @version 2.0
	  NOTES :
	  Chaque item peux avoir un couple valeur/libellé (séparateur '=') "val=Mon Libellé"
	  Chaque item peux avoir des options (séparateur item/option  '|'):
	  USAGE :
	  amu.initCmb("monIDCombo","val1=Libellé val1|tips val1|col fond val1,valN=Libellé val N||tips valN|col fond valN",true/false);
	 */	
initCmb:function(id,listValues,addfirstempty) // version  2 à mettre dans amu.js aussi
{
  var e=document.getElementById(id);
  addfirstempty=(addfirstempty==null)?true:addfirstempty;
  if(e!=null)
  {
    try {
      amu.razCmb(id);
    }catch(ex){}
    if(addfirstempty)
    {	/* ajout 1ier val à "" */
      var opt0=document.createElement('option');
      opt0.text="";
      opt0.value="";
      opt0.label="";
      try	{
        e.add(opt0);
      }/*IEonly*/catch(ex){
        try {
          e.add(opt0,null);
        }catch(ex){}
      }/*allOthers*/
  }
  var tab=Array();
  if(listValues!=undefined)  if(listValues!=null) if(listValues.length>1) tab=listValues.split(",");
  for(var i=0;i<tab.length;i++) /*ajout des autres vals*/
  {
    var val=tab[i].replace('=','');
    var txt=tab[i].replace('=','');
    var tips=cls=bg="";
    try{
      var tabText=tab[i].toString().split("=");
      if(tabText.length>1){
        if(tabText!=null)if(tabText[0]!=null) val=tabText[0];
        if(tabText[1]!=null) txt=tabText[1];
      }
    }catch(ex) { } //ex=null; continue;
    try{
      var tabText2=txt.toString().split("|");
      if(tabText2!=null)if(tabText2[1]!=null) txt=tabText2[0];
      if(tabText2[1]!=null) tips=tabText2[1];
      if(tabText2[2]!=null) bg=tabText2[2];
      if(tabText2[3]!=null) cls=tabText2[3];
    }catch(ex) { } //ex=null; continue;
    var opt=document.createElement('option');
    opt.text=txt;
    opt.label=txt;
    opt.value=val;
    if(typeof(tips)!=undefined)
      if(tips!="")
      {
        $(opt).attr('title',tips);
        $(opt).addClass(amu._tipsclass);
        tips_active=true; 
        if(typeof(bg)!=undefined)
          if(bg!="") $(opt).css('background',bg);
        if(cls!="") $(opt).addClass(cls);
      }
    try{
      e[e.length]=opt; /*IEonly*/
    }	catch(ex){
      e.add(opt,null); /* Std compliant*/
    }
  }
}
},
  
razCmb:function(id){
  var e=document.getElementById(id);
  if(e) 
  { try{ 
    while(e.options.length>0){
     e.options[0]=null;
       }  
    }
    catch(ex){}
  } else alert('razCmb/razCmbValues : id '+id+" n'existe pas !!!");
},
razCmbValues:function(id_list) {
  id_list +=',';
  var aEl=id_list.split(",");
  for(var i=0;i<aEl.length;i++) if(aEl[i]!=''){
    amu.razCmb(aEl[i]);
  }
  },
raz:function(fields_list,defVal){
  if(defVal==null) defVal='';
  fields_list='#'+fields_list.replace(',',',#');
  $(fields_list).val(defVal);
},

 /* @description Calcul et renvoi la clef valide pour un numero INSEE donnée  @param code (string) le code INSEE */
	insee:
	{ 
		key:function(code)
		{var tmp=new String(code);
			tmp=tmp.replace("PROVIS","796592");// si N° insee PROVIsoire..
			tmp=tmp.replace("2A","19");
			tmp=tmp.replace("2B","18");
			var num=parseInt(tmp,10);
			var key =( 97 - (num - Math.floor(num / 97) * 97) );
			if(key<10) key="0"+key;
			return(key);
		},
		/* @description Analyse et renvoi un descriptif texte complet concernant un numero INSEE donnée * @param code (string) le code INSEE */
		explain:function(code)
		{var s=new String();var c=new String(code);
			if(c.length==13)		//if(!isNaN(c))
			{c=c.split("");
				s=s+'\n [0]   Sexe => '+((c[0]=='1')?'Masculin':'Féminin');  //	1 pour les hommes, 2 pour les femmes
				s=s+'\n [1-2] Année de naissance => XX'+c[1]+c[2];
				var n_mois=c[3]+c[4];
				s=s+'\n [3-4] Mois de naissance => '+n_mois;
				if(n_mois>12) s=s+'\nMois>12 => cas exceptionnel:état civil incomplet (>20)';
				var dep=c[5]+c[6];
				s=s+'\n [5-6] Département de naissance => '+dep;
				var n_codcom=c[8]+c[9]+c[10];
				if(dep=="99")
				{s=s+'\n [Dep Naiss=99 => ETRANGER. : code commune [8-10] => code pays naiss]';
					s=s+'\n [8-10] Code PAYS Naissance => '+n_codcom;
				}
				else
				{s=s+'\n [8-10] Code Commune Naissance => '+n_codcom;
					s=s+'\n [11-12] N° ordre acte de naissance ='+c[11]+c[12];
				}
			}
		return(s);
		}
	},
	
  /*@description Calcul et renvoi le 'numero de cle' valide pour des coordonnees bancaire donnees (banque,guichet,compte)
  * @param banque le code de la Banque
  * @param guichet le numero de guichet de la Banque
  * @param compte le numéro de compte en Banque
  * @author mubeda
  * @since (16/10/2008) modif 18/03/2010  */
	rib:
	{
		key:function(banque,guichet,compte)
		{var k=false;
			if( (banque.toString().length==5) && (guichet.toString().length==5) && (compte.toString().length==11) )
			{function replaceAlpha(alpha){return('12345678912345678923456789'.charAt(alpha.charCodeAt(0) - 65));}
				compte= parseInt(compte.toString().toUpperCase().replace(/[A-Z]/g, replaceAlpha), 10);
		    k=( 97 - (((parseInt(banque, 10)% 97 * 100000 + parseFloat(guichet)) % 97 * 100000000000 + compte) % 97) * 100 % 97);
			} 
			return(k);
		}	
	},
	
  /*@description convertie une date en dd/mm/YYYY HH:mm:ss en Javascript Object Date
   * Resolv bug Javascript Mois-1 */
  dateFromDMY:function(value){ var dt=value.split(/[\/: ]/); var d=new Date(dt[2],dt[1]-1,dt[0],((dt.lenght>3)?dt[3]:0),((dt.lenght>4)?dt[4]:0),((dt.lenght>5)?dt[5]:0)); return(d); },
  /*@description permet de traduire une valeur en secondes dans la corresppondance suivante : " (soit X minute[s] et Y seconde[s])" @param float value @returns String */
  cnvSec2TxtMinSec:function(value){ var m=((value>60)?Math.floor(value/60):0); var s=((m>0)?amu.floatP(value-(m*60),2):value); return(" (soit "+m+" minute"+((m>1)?"s":"")+" et "+s+" seconde"+((s>1)?"s":"")+")" ); },
	/*@description permet d'arrondir un float à la precision souhaité (0.01 par defaut ssi précision non specifie) @param val la valeur à arondir @param precision la precision souhaite (0.01 par défaut) */
	floatP:function(val,precision){if(!precision)precision=2;var p=Math.pow(10,precision);var f=Math.round(val*p)/p;return(f);},
	/*@description Enleve dans un element HTML tout les caracteres autre que les chiffres et les point (catrctère constituant une valeur FLOAT) @param id l'identifiant de l'element*/
  normToFloat:function(id){var v=$("#"+id).val();if(v!=''){if(v.search(/[^0-9.]/)!=-1) v=v.replace(/,/,'.').replace(/[^0-9.]/,'');$("#"+id).val(v);}},
  str_BetweenAB:function(text,A,B,retStrIncludeAB){if(retStrIncludeAB==null) retStrIncludeAB=false;var result="";var posDeb=text.indexOf(A);if(posDeb!=-1){if(!retStrIncludeAB) posDeb=posDeb+A.length;var posFin=text.indexOf(B,posDeb);if(retStrIncludeAB) posFin=posFin+B.length;if(posFin!=-1) if(posFin>posDeb) result=text.slice(posDeb,posFin);}return(result);},
  normJSON:function(str,nullCar){ if(nullCar==null) nullCar=""; if(str==null) return(nullCar);  else{ str=str.toString().replace(/'/,"&apos;").replace(/,/,"&#44;").replace(/:/,"&#58;").replace(/;/,"&#59;").replace(/"/,"&quot;");   } return(str);  },
  
  /**
   * Generation d'un tableau pde roposition de login/uid pour un Nom et un Prénom
   * @param {string} name Nom
   * @param {string} surname Prénom
   * @param {string} prefix (option) default=""
   * @returns {array}
   */
  str_arProposeUid:function(name,surname,prefix){
    var r=new Array();
    if(prefix===null) prefix="";
    var n=amu.str_NormalizeAccentsLower(name.toLowerCase()).replace(/[^a-z]/g,'');
    var s=amu.str_NormalizeAccentsLower(surname.toLowerCase()).replace(/[^a-z]/g,'');
    var iS="";
    var aS= (surname.toLowerCase()+" ").split(/[ \-,.]+/g);
    if(aS.length>1){ for(var i=0;i<aS.length;i++){ iS = iS + aS[i].charAt(0); } }else{ iS= aS.charAt(0); }
    r[0]= prefix+s.charAt(0)+n;
    r[1]= prefix+iS+n;
    r[2]= prefix+iS+"."+n;
    r[3]= prefix+s+"."+n;
    r[4]= prefix+s+n;
    return(r);
 },
 str_randPass: function (len, sp) {
    var i = 0;var p = ""; var r;
    if(len == undefined){var len = 8;}
    if(sp == undefined){var sp = false;}
    while(i < len){
      r = (Math.floor((Math.random() * 100)) % 94) + 33;
      if(!sp){
        if ((r >=33) && (r <=47)) { continue; }
        if ((r >=58) && (r <=64)) { continue; }
        if ((r >=91) && (r <=96)) { continue; }
        if ((r >=123) && (r <=126)) { continue; }
      }
      i++;
      p += String.fromCharCode(r);
    }
    return p;
  },
  str_randPass2:function(len,ch){
    if(len == undefined){var len = 8;}
    if(ch == undefined){var ch = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890,.;*&-_@+%?!$";}
    var max=ch.length -1; p = "";
    for(var x=0;x<len;x++){ var i = Math.floor(Math.random() * max); p += ch.charAt(i); }
    return p;
  },
  str_NormalizeAccents:function(txt,lower,upper){lower=((lower===null)?true:lower);upper=((upper===null)?true:upper);if(lower){txt=amu.str_NormalizeAccentsLower(txt);}if(upper){txt=amu.str_NormalizeAccentsUpper(txt);}return(txt);},
	str_NormalizeAccentsLower:function(txt){txt=txt.replace(/[äàáâ]/g,'a');txt=txt.replace(/[ëêéè]/g,'e');txt=txt.replace(/[ïîìí]/g,'i');txt=txt.replace(/[öôòó]/g,"o");txt=txt.replace(/[üûùú]/g,"u");txt=txt.replace("ç","c");return(txt);},
	str_NormalizeAccentsUpper:function(txt){txt=txt.replace(/[ÄÂÁÀ]/g,'A');txt=txt.replace(/[ËÊÉÈ]/g,'E');txt=txt.replace(/[ÏÎÌÍ]/g,'I');txt=txt.replace(/[ÖÔÓÒ]/g,"O");txt=txt.replace(/[ÜÛÙÚ]/g,"U");txt=txt.replace("Ç","C");return(txt);},

  serializeChkValues:function(id_list) {
  var strvals='';
  id_list +=',';
  var aEl=id_list.split(",");
  for(var i=0;i<aEl.length;i++){
    if(aEl[i]!=''){
      var e=null;
      e=document.getElementById(aEl[i]);
      if(e!=null){
        if(e.checked){
          strvals +=e.value+',';
        }
      }
    }
  }
return(strvals.slice(0,strvals.length-1));
}

};

// http://jacwright.com/projects/javascript/date_format
Date.prototype.format=function(format){var returnStr='';var replace=Date.replaceChars;for(var i=0;i<format.length;i++){var curChar=format.charAt(i);if(i-1>=0&&format.charAt(i-1)=="\\"){returnStr+=curChar;}else if(replace[curChar]){returnStr+=replace[curChar].call(this);}else if(curChar!="\\"){returnStr+=curChar;}}return returnStr;};Date.replaceChars={shortMonths:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],longMonths:['January','February','March','April','May','June','July','August','September','October','November','December'],shortDays:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],longDays:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],d:function(){return(this.getDate()<10?'0':'')+this.getDate();},D:function(){return Date.replaceChars.shortDays[this.getDay()];},j:function(){return this.getDate();},l:function(){return Date.replaceChars.longDays[this.getDay()];},N:function(){return this.getDay()+1;},S:function(){return(this.getDate()%10==1&&this.getDate()!=11?'st':(this.getDate()%10==2&&this.getDate()!=12?'nd':(this.getDate()%10==3&&this.getDate()!=13?'rd':'th')));},w:function(){return this.getDay();},z:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((this-d)/86400000);},W:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((((this-d)/86400000)+d.getDay()+1)/7);},F:function(){return Date.replaceChars.longMonths[this.getMonth()];},m:function(){return(this.getMonth()<9?'0':'')+(this.getMonth()+1);},M:function(){return Date.replaceChars.shortMonths[this.getMonth()];},n:function(){return this.getMonth()+1;},t:function(){var d=new Date();return new Date(d.getFullYear(),d.getMonth(),0).getDate()},L:function(){var year=this.getFullYear();return(year%400==0||(year%100!=0&&year%4==0));},o:function(){var d=new Date(this.valueOf());d.setDate(d.getDate()-((this.getDay()+6)%7)+3);return d.getFullYear();},Y:function(){return this.getFullYear();},y:function(){return(''+this.getFullYear()).substr(2);},a:function(){return this.getHours()<12?'am':'pm';},A:function(){return this.getHours()<12?'AM':'PM';},B:function(){return Math.floor((((this.getUTCHours()+1)%24)+this.getUTCMinutes()/60+this.getUTCSeconds()/3600)*1000/24);},g:function(){return this.getHours()%12||12;},G:function(){return this.getHours();},h:function(){return((this.getHours()%12||12)<10?'0':'')+(this.getHours()%12||12);},H:function(){return(this.getHours()<10?'0':'')+this.getHours();},i:function(){return(this.getMinutes()<10?'0':'')+this.getMinutes();},s:function(){return(this.getSeconds()<10?'0':'')+this.getSeconds();},u:function(){var m=this.getMilliseconds();return(m<10?'00':(m<100?'0':''))+m;},e:function(){return"Not Yet Supported";},I:function(){return"Not Yet Supported";},O:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+'00';},P:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+':00';},T:function(){var m=this.getMonth();this.setMonth(0);var result=this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/,'$1');this.setMonth(m);return result;},Z:function(){return-this.getTimezoneOffset()*60;},c:function(){return this.format("Y-m-d\\TH:i:sP");},r:function(){return this.toString();},U:function(){return this.getTime()/1000;}};

/*

String.prototype.removeNum=function(){return this.replace(/[0-9]/,'');};
String.prototype.removeAlpha=function(){return this.val.replace(/[^0-9]/,'');};
String.prototype.trim=function(){var rs=/^[^\s]+/g; var re=/[^\s]+$/g; return this.replace(rs,'').replace(re,'');};
String.prototype.removespace=function(){var r=/[ ]+/g;return this.replace(r,'');};
String.prototype.padLeft=function(car,len){var r=this+'';while(r.length!=len){r=car+r;} return r;};
String.prototype.padRight=function(car,len){var r=this+'';while(r.length!=len){r=r+car;} return r;};
String.prototype.encode_utf8=function(s){return unescape(encodeURIComponent(s));};
String.prototype.decode_utf8=function(s){return decodeURIComponent(escape(s));};

String.prototype.isValidDate=function(){var v=true;var m=[31,28,31,30,31,30,31,31,30,31,30,31];	var e=/^([\d]{2}\/){2}\d{4}$/; var vs=this.split('/'); return e.test(this)?(parseInt(vs[1])>12||parseInt(vs[1])<1?false:((parseInt(vs[1])!=2)?(parseInt(vs[0])>m[parseInt(vs[1])-1]?false:true):(parseInt(vs[0])>(vs[2]%4==0?m[1]+1:m[1])?false:true))):false;	};
String.prototype.isPhoneFr=function(){var r=/^0\d{9,}$/;return r.test(this)?true:false;};
String.prototype.isPhoneInt=function(){var r=/^[\+{1} ?]\d{7,}$/;return r.test(this)?true:false;};
String.prototype.isMail=function(){var r=/^.+@.+\.([a-z]{2,})$/i;return r.test(this)?true:false;};

// http://jsfromhell.com/geral/utf-8 [v1.0]
//UTF8 encode / decode
  */
 
// Traduction FR
$.datepicker.regional['fr'] = { // Default regional settings
  closeText: 'Ok', // Display text for close link
  prevText: 'Pr&eacute;c&eacute;dent', // Display text for previous month link
  nextText: 'Suivant', // Display text for next month link
  currentText: 'Ajourd&apos;hui', // Display text for current month link
  monthNames: ['Janvier','F&eacute;vrier','Mars','Avril','Mai','Juin','Juillet','Ao&ucirc;t','Septembre','Octobre','Novembre','D&eacute;cembre'], // Names of months for drop-down and formatting
  monthNamesShort: ['Jan', 'F&eacute;v', 'Mar', 'Avr', 'Mai', 'Jui', 'Jui', 'Ao&ucirc;', 'Sep', 'Oct', 'Nov', 'D&eacute;c'], // For formatting
  dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'], // For formatting
  dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'], // For formatting
  dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'], // Column headings for days starting at Sunday
  weekHeader: '<font color="gray" title="Semaine num&eacute;ro...">Se</font>', // Column header for week of the year
  showWeek: true, // afficher le numero de semaine
  dateFormat: 'dd/mm/yy', // See format options on parseDate
  firstDay: 1, // The first day of the week, Sun = 0, Mon = 1, ...
  isRTL: false, // True if right-to-left language, false if left-to-right
  showMonthAfterYear: false, // True if the year select precedes month, false for month then year
  yearSuffix: '' // Additional text to append to the year in the month headers
};

// Traduction Select2
 $.extend($.fn.select2.defaults, {
        formatMatches: function (matches) { return matches + " résultats sont disponibles, utilisez les flèches haut et bas pour naviguer."; },
        formatNoMatches: function () { return "<font color=red>Aucun résultat trouvé</font>"; },
        formatInputTooShort: function (input, min) { var n = min - input.length; return "Merci de saisir " + n + " caractère" + (n == 1 ? "" : "s") + " de plus"; },
        formatInputTooLong: function (input, max) { var n = input.length - max; return "Merci de supprimer " + n + " caractère" + (n == 1 ? "" : "s"); },
        formatSelectionTooBig: function (limit) { return "<font color=orange>Vous pouvez seulement sélectionner " + limit + " élément" + (limit == 1 ? "" : "s")+"</font>"; },
        formatLoadMore: function (pageNumber) { return "<font color=blue>Chargement de résultats supplémentaires…</font>"; },
        formatSearching: function () { return "<font color=blue>Recherche en cours…</font>"; }
  });
 

/* Traduction non implémenté ...
 * 
$.fullCalendar.setDefaults({
  allDayDefault:false,
  allDaySlot:false,
  theme:true,
  weekNumbers:true,
  weekNumberTitle:'Semaine N°',
  header:
  { left: 'prev,next today',
    center: 'title',
    right: 'month,agendaWeek,agendaDay',
  },
  allDayText: 'Tous les jours',
  axisFormat: 'HH:mm',
  titleFormat:
     { month: 'MMMM yyyy',
       week: "'Semaine du' d [ MMMM yyyy]{ 'au' d MMMM yyyy}",
       day: 'dddd d MMMM yyyy'
     },
	columnFormat:
  { month: 'dddd',
    week: 'dddd d MMMM',
    day: 'dddd d MMMM'
  },
	timeFormat: 
  { '': 'HH:mm',
		agenda: 'HH:mm{ - HHJ:mm}'
	},
	firstDay: 1,
  monthNames:['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juilet','Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
  monthNamesShort:['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin','Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
  dayNames:['Dimanche', 'Lundi', 'Mardi', 'Mecredi', 'Jeudi', 'Vendredi', 'Samedi'],
  dayNamesShort:['Dim', 'Lun', 'Mar', 'Mec', 'Jeu', 'Ven', 'Sam'],   
	buttonText: {
		prev: '&nbsp;&#9668;&nbsp;',
		next: '&nbsp;&#9658;&nbsp;',
		prevYear: '&nbsp;&lt;&lt;&nbsp;',
		nextYear: '&nbsp;&gt;&gt;&nbsp;',
		today: "Aujourd'hui",
		month: 'Mois',
		week: 'Semaine',
		day: 'Jour'
	},
  eventRender: function(event,element){
      var myCSS = {};
      if(event.color !== '') myCSS['background-color'] = event.color;
      if(event.description!== ''){
        element.attr("title",'Informations...|<small>'+event.description+'</small>');
        element.addClass("cluetip");
        element.addClass("text-shadow","2px 2px 2px rgba(255, 255, 255, 0.3)");
       }
       $('.fc-event-inner',element).add(element).css(myCSS);
   },
  eventAfterAllRender: function(){
    $(".fc-week-number div").css("color","orange");
    amu.initTips();
  }
});
*/
try{
  
  Highcharts.setOptions({
    lang: {
      contextButtonTitle: 'Menu contextuelle du graphique',
      //decimalPoint: '.',
      downloadJPEG: "T&eacute;l&eacute;charger en image JPEG",
      downloadPDF: "T&eacute;l&eacute;charger au format PDF",
      downloadPNG: "T&eacute;l&eacute;charger en image PNG",
      downloadSVG: "T&eacute;l&eacute;charger en image vectorielle SVG",
      loading: "Chargement en cours...",
      months: ['Janvier', 'F&eacute;vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao&ucirc;t', 'Septembre', 'Octobre', 'Novembre', 'D&eacute;cembre'],
      //numericSymbols:
      printChart: "Imprimer le graphique",
      resetZoom: "Retour &agrave; l'&eacute;chelle par d&eacute;faut",
      resetZoomTitle: "Zoom &agrave; l'&eacute;chelle r&eacute;el (1:1)",
      shortMonths: ['Jan', 'F&eacute;v', 'Mar', 'Avr', 'Mai', 'Jui', 'Jui', 'Ao&ucirc;', 'Sep', 'Oct', 'Nov', 'D&eacute;c'],
      thousandsSep: '',
      weekdays: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
    }
  });
  
} catch(ex){} 

$.datepicker.setDefaults($.datepicker.regional["fr"]); 

// permet le html dans les tooltips html
$.widget("ui.tooltip", $.ui.tooltip, {
    options: {
        content: function () {
            return $(this).prop('title');
        }
    }
});
