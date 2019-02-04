/**
 * Created by peggy_fernandez on 18/04/2016.
 */

function initdatatable(elt) {
    $(elt).DataTable({
        "language": {
            "lengthMenu": 'Afficher <select>'+
            '<option value="10">10</option>'+
            '<option value="25">25</option>'+
            '<option value="50">50</option>'+
            '<option value="100">100</option>'+
            '<option value="-1">Tous</option>'+
            '</select> éléments',
            "zeroRecords": "Aucune entrée trouvée",
            "info": "Affichage éléments _START_ à _END_ sur _TOTAL_ éléments",
            "infoEmpty": "Pas d'élément disponible",
            "search":         "Recherche ",
            "infoFiltered":   "(filtrage sur un total de _MAX_ éléments)",
            "paginate": {
                "first":      "Premier",
                "last":       "Dernier",
                "next":       "Suivant",
                "previous":   "Précédent"
            }
        },
        "iDisplayLength": 25
    })
};

function initdatatable_groupupdate(elt) {
    $(elt).DataTable({
        "language": {
            "lengthMenu": 'Afficher <select>'+
            '<option value="10">10</option>'+
            '<option value="25">25</option>'+
            '<option value="50">50</option>'+
            '<option value="100">100</option>'+
            '<option value="-1">Tous</option>'+
            '</select> éléments',
            "zeroRecords": "Aucune entrée trouvée",
            "info": "Affichage éléments _START_ à _END_ sur _TOTAL_ éléments",
            "infoEmpty": "Pas d'élément disponible",
            "search":         "Recherche ",
            "infoFiltered":   "(filtrage sur un total de _MAX_ éléments)",
            "paginate": {
                "first":      "Premier",
                "last":       "Dernier",
                "next":       "Suivant",
                "previous":   "Précédent"
            }
        },
        "columns": [
            null, null,null, null, null, null,
            { "orderDataType": "dom-checkbox" },
            { "orderDataType": "dom-checkbox" }
        ],
        "iDisplayLength": 25
    })
};


function initdatatable_userupdate(elt) {
    $(elt).DataTable({
        "language": {
            "lengthMenu": 'Afficher <select>'+
            '<option value="10">10</option>'+
            '<option value="25">25</option>'+
            '<option value="50">50</option>'+
            '<option value="100">100</option>'+
            '<option value="-1">Tous</option>'+
            '</select> éléments',
            "zeroRecords": "Aucune entrée trouvée",
            "info": "Affichage éléments _START_ à _END_ sur _TOTAL_ éléments",
            "infoEmpty": "Pas d'élément disponible",
            "search":         "Recherche ",
            "infoFiltered":   "(filtrage sur un total de _MAX_ éléments)",
            "paginate": {
                "first":      "Premier",
                "last":       "Dernier",
                "next":       "Suivant",
                "previous":   "Précédent"
            }
        },
        "columns": [
            null,
            { "orderDataType": "dom-checkbox" },
            { "orderDataType": "dom-checkbox" }
        ],
        "iDisplayLength": 25
    })
};

function initdatatable_add(elt) {
    $(elt).DataTable({
        "language": {
            "lengthMenu": 'Afficher <select>'+
            '<option value="10">10</option>'+
            '<option value="25">25</option>'+
            '<option value="50">50</option>'+
            '<option value="100">100</option>'+
            '<option value="-1">Tous</option>'+
            '</select> éléments',
            "zeroRecords": "Aucune entrée trouvée",
            "info": "Affichage éléments _START_ à _END_ sur _TOTAL_ éléments",
            "infoEmpty": "Pas d'élément disponible",
            "search":         "Recherche ",
            "infoFiltered":   "(filtrage sur un total de _MAX_ éléments)",
            "paginate": {
                "first":      "Premier",
                "last":       "Dernier",
                "next":       "Suivant",
                "previous":   "Précédent"
            }
        },
        "columns": [
            null, null,
            { "orderDataType": "dom-checkbox" },
            { "orderDataType": "dom-checkbox" }
        ],
        "iDisplayLength": 25
    })
};
