# DOI Workflow-Erweiterung für OMP & OJS

## 1. Funktionsumfang
Das Plugin fügt im OMP und OJS Backend auf der DOI-Seite jedem einzelnen Element, welches noch keine DOI hat, einen Button "DOI zuweisen" bzw. "Assign DOI" hinzu. Über die Plugin-Einstellungen kann man wählen, ob unter "Bulk Aktionen" der Button "DOIs hinzufügen" vorhanden sein soll oder nicht.
In OJS muss diese Wahl für Artikel und Ausgaben separat erfolgen. Standardmäßig werden diese Buttons ausgeblendet.

## 2. Installation
Das Root-Verzeichnis der OMP oder OJS Installation wird im Folgenden `/PKP-Root` genannt.  

Zur Installation des Plugins muss dieses mittels  
`git clone https://github.com/UB-Heidelberg/doiWorkflowEnhancement.git`  
im Verzeichnis `/PKP-Root/plugins/generic/` erzeugt werden.

Nun sichern wir die original build.js-Datei Ihrer Installation um Sie im Notfall einfach wieder herstellen zu können.
Hierzu wechseln Sie in das Verzeichnis `/PKP-Root/js/`.  

Benennen Sie die Datei build.js um:   
`mv "build.js" "build.js.original"`  

Nun müssen wir die build.js-Datei aus dem Plugin in dieses Verzeichnis kopieren. Da es Unterschiede in der genutzten Version der UI-Library von OJS zu OMP geben kann, verfügt das Plugin über unterschiedliche build.js-Dateien für OMP und OJS.  
OMP:`cp -i /PKP-Root/plugins/generic/doiWorkflowEnhancement/js/omp/build.js /PKP-Root/js/`  
OJS:`cp -i /PKP-Root/plugins/generic/doiWorkflowEnhancement/js/ojs/build.js /PKP-Root/js/`  

Gehen Sie nun in das OJS/OMP-Backend und aktivieren Sie das Plugin.

Sollte es gewünscht sein die "Bulk Aktion" "DOIs zuweisen" weiter nutzen zu können, muss in den Plugin-Einstellungen der entsprechende Haken gesetzt werden.  

Sollten die neuen Buttons nicht angezeigt werden, hat Ihr Browser vermutlich noch die original build.js-Datei im Cache. Nach einer Leerung des Browsercaches sollte dieses Problem behoben sein.

## 3. Bau einer neuen build.js-Datei
### (Nur für Entwickler notwendig, die nach einem OMP/OJS Upgrade selbst eine neue build.js-Datei für dieses Plugin erstellen möchten.)

### 3.1 Einrichtung
Klonen Sie das GitHub-Repository von OJS bzw. OMP.

Gehen Sie ins Root-Verzeichnis des Repositories.

Wechseln Sie mit `git checkout NameDesGewünschtenBranchs` zu dem gewünschten Branch.

Laden Sie nun die Submodule mit  
`git submodule init`  
und anschließendem  
`git submodule update`.

Nun folgt noch:  
`npm install`

### 3.2 Anpassungen
Das Root-Verzeichnis der OMP oder OJS Installation wird im Folgenden `/PKP-Root` genannt.  

Es müssen zwei Dateien aus der UI-Library angepasst werden:
`/PKP-Root/lib/ui-library/src/components/ListPanel/doi/DoiListPanel.vue`  
und  
`/PKP-Root/lib/ui-library/src/components/ListPanel/doi/DoiListItem.vue`

Bearbeiten Sie diese Dateien mit einem Editor Ihrer Wahl.

Für ein besseres Verständnis finden Sie diese Dateien auch in bereits angepasster Form im Plugin im Ordner "src". Die Zeilen Angaben im Folgenden beziehen sich auf diese Dateien. Aktuell unterscheiden sich diese Dateien nicht hinsichtlich OMP & OJS. 

#### 3.2.1 DoiListPanel.vue
In dieser Datei sind nur zwei Ergänzungen notwendig. Sie dienen beide dazu die URL für den Ajax-Request an die andere Komponente weiterzugeben.

##### OMP & OJS Zeile 178
Suchen Sie im Template-Bereich der Datei nach dem Tag `<doi-list-item` und fügen diesem als weiteres Attribut `:doi-workflow-enhancement-plugin-url="doiWorkflowEnhancementPluginUrl"` hinzu.

#### OMP & OJS Zeilen 368 - 371
Ergänzen Sie unter `export default` -> `props` folgendes Element:  
```
doiWorkflowEnhancementPluginUrl: {
    type: String,
	required: true,  
},
```  

#### 3.2.2 DoiListItem.vue
In dieser Datei müssen wir der Tabelle eine weitere Spalte mit dem Button hinzufügen, die Ajax-Methode für den Button, definieren und eine weitere Methode ergänzen.
Die Ergänzung der Tabelle erfolgt zweimal, da die Nutzung in einem Modal-Element separat definiert ist.

##### OMP & OJS Zeile 88 - 95  
Ergänzung der Tabelle durch das Hinzufügen einer neuen Zelle:
```
<table-cell :column="doiListColumns[2]" :row="row">
	<pkp-button
		v-show="!isEditingDois && !(mutableDois.find((doi) => doi.uid === row.uid).identifier) && (enabledDoiTypes.find((el) => row.uid.includes(el)) || row.uid.includes('monograph') || row.uid.includes('article'))"
		:id="row.uid + '-assign'"
		@click="assignDoi(row.uid)">
		{{ __('plugins.generic.doiWorkflowEnhancement.button.assignDoi') }}
	</pkp-button>
</table-cell>
```
> Hinweis:  
> Mittels v-show wird hier geregelt, ob der Button angezeigt wird oder nicht:
> - !isEditingDois: Button wird nicht angezeigt, wenn man im DOI-Bearbeitungsmodus ist.
> - !(mutableDois.find((doi) => doi.uid === row.uid).identifier): Button wird nicht angezeigt, wenn bereits eine DOI zugewiesen ist.
> - (enabledDoiTypes.find((el) => row.uid.includes(el)) || row.uid.includes('monograph') || row.uid.includes('article')): Der Button wird nur angezeigt, wenn der Inhaltstyp für DOIs zulässig ist. Monograph und Article müssen hier separat behandelt werden, da sie im Array der erlaubten Typen als Publication geführt werden.  

##### OMP & OJS Zeile 258 - 265  
Ergänzung der Tabelle (Modal) durch den gleichen Code wie in den Zeilen 88 - 95.

##### OMP & OJS Zeile 309 - 312
Ergänzen Sie unter `export default` -> `props` folgendes Element:
```
doiWorkflowEnhancementPluginUrl: {
    type: String,
	required: true,  
},
```  
Durch diesen Eintrag wird die für den Ajax-Request benötigt URL bereitgestellt.

##### OMP & OJS Zeile 405 - 409
Ergänzen Sie unter `export default` -> `data` -> `return` -> `doiListColumns` folgenden Eintrag:
```
{
	name: 'assign',
	label: '',
	value: 'value',
},
```  
Durch diesen Eintrag wird die neue Tabellenspalte angelegt. Unter `label` wäre auch eine Spaltenüberschrift möglich, auf die hier jedoch verzichtet wurde.

##### OMP & OJS Zeile 609 - 611
Ergänzen Sie die Methode `saveDois` unter `export default` -> `methods` um folgende Zeilen:
```
// Reload DOM after saving by close and open expander
this.$emit('expand-item', this.item.id);
this.$emit('expand-item', this.item.id);
```  
> Hinweis:  
> Bearbeitete man eine DOI und entfernte sie, wurde das ausgeklappte Element nicht neu geladen und somit auch nicht der neue Button "DOI hinzufügen" angezeigt. Um diesen Fehler zu beheben wird durch diesen Code das Element geschlossen und gleich wie geöffnet. Dadurch wird der Inhalt aktualisiert und der Button korrekt angezeigt.

##### OMP & OJS Zeile 856 - 898
Unter `export default` -> `methods` ist nun noch folgende Methode für den Ajax-Request zu ergänzen:
```
/**
* AJAX call to assign a single DOI
*/
assignDoi(uid) {
	let self = this;

	return $.ajax({
	    url: `${this.doiWorkflowEnhancementPluginUrl}`,
	    type: 'POST',
	    headers: {
		    'X-Csrf-Token': pkp.currentUser.csrfToken,
		    contentType: 'application/json',
	    },
	    dataType: 'json',
	    data: {
		    uid: `${uid}`,
		    id: this.item.id,
	    },
	    success(response) {
		    $('#' + response.content.uid).val(response.content.doi);

		    // Get updated submission or issue
		    $.ajax({
			    url: `${self.apiUrl}/${response.content.submissionId}`,
			    type: 'GET',
			    success(response) {
			        self.$emit('update-successful-doi-edits', response);
		        },
			    error(response) {
				    self.ajaxErrorCallback(response);
				    // Or tell DOI list panel reload everything
				    // or force reload entire page
				},
			    complete(response) {
				    self.itemsToUpdate = {};
				},
		    });
	    },
	    error: (response) => {
		    this.postUpdatedDoiError(response, response.content.uid);
	    },
    });
},
```  
### 3.2 Erzeugen der build.js-Datei
Nach den Anpassungen der .vue-Dateien gehen Sie wieder in das Root-Verzeichnis des Repositories.

Führen Sie nun folgenden Befehl aus:  
`npm run build`  

Im Ordner `js/` finden Sie nun die neu erstellte build.js-Datei. 