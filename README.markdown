# Template Selectbox Field
 
* Version: 1.0
* Author: [Nick Ryall](http://randb.com.au)
* Build Date: 2012-02-13
* Requirements: Symphony 2.2

## Installation
 
1. Upload the 'templateselectboxfield' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Field: Template Select Box", choose Enable from the with-selected menu, then click Apply.
3. The field will be available in the list when creating a Section.

## Usage


1. Create a new folder within your workspace for your custom templates.
2. Create your XSLT files and place them in the new folder. You can optionally give your templates a user-friendly name by adding a comment to the top of your file as follows:

	<code><!--<br />
		     Template: Template Name<br />
	--></code>

3. Adding this field to a section and select your new template folder.
4. When editing/creating entries in your new section, you will now see a 'Page Template' field where you can select your custom templates. Note: By default the existing page template is used. 
5. Attach the field to the main datasource you are using for retrieving entries from your section (note: only 1 page template field can be used per Symphony Page). 


## Credits

I used  Nick Dunn's & Brendan Abbott's Upload Select Box Field as a starting point for this extension.