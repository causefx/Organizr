(function(jsGrid) {

    jsGrid.locales.fr = {
        grid: {
            noDataContent: "Pas de données",
            deleteConfirm: "Êtes-vous sûr ?",
            pagerFormat: "Pages: {first} {prev} {pages} {next} {last} &nbsp;&nbsp; {pageIndex} de {pageCount}",
            pagePrevText: "<",
            pageNextText: ">",
            pageFirstText: "<<",
            pageLastText: ">>",
            loadMessage: "Chargement en cours...",
            invalidMessage: "Des données incorrectes sont entrés !"
        },

        loadIndicator: {
            message: "Chargement en cours..."
        },

        fields: {
            control: {
                searchModeButtonTooltip: "Recherche",
                insertModeButtonTooltip: "Ajouter une entrée",
                editButtonTooltip: "Changer",
                deleteButtonTooltip: "Effacer",
                searchButtonTooltip: "Trouve",
                clearFilterButtonTooltip: "Effacer",
                insertButtonTooltip: "Ajouter",
                updateButtonTooltip: "Sauvegarder",
                cancelEditButtonTooltip: "Annuler"
            }
        },

        validators: {
            required: { message: "Champ requis" },
            rangeLength: { message: "Longueur de la valeur du champ est hors de la plage définie" },
            minLength: { message: "La valeur du champ est trop long" },
            maxLength: { message: "La valeur du champ est trop court" },
            pattern: { message: "La valeur du champ ne correspond pas à la configuration définie" },
            range: { message: "La valeur du champ est hors de la plage définie" },
            min: { message: "La valeur du champ est trop grande" },
            max: { message: "La valeur du champ est trop petit" }
        }
    };

}(jsGrid, jQuery));

