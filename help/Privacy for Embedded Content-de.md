<!--
# media module
# privacy for embedded content, German
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/activities
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2025 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->

# Datenschutz für eingebettete Inhalte

Eingebettete Inhalte von Drittservern, z. B. Videos, werden der
Besucherin der Website nicht direkt angezeigt. Stattdessen wird um
Zustimmung gebeten, ob diese Inhalte direkt auf der Website angezeigt
werden sollen. Alternativ ist es möglich, über einen Link diese Inhalte
direkt auf der anderen Website anzuschauen.

Die Zustimmung wird in einem Cookie gespeichert. Weitere eingebette
Inhalte werden fortan direkt angezeigt.

Um die Zustimmung zu einem späteren Zeitpunkt widerrufen zu können,
sollte (z. B. in der Datenschutzerklärung) eine Möglichkeit eingebaut
werden, die eigenen Einstellungen zu ändern. Dies funktioniert über den
Block:

    %%% request embedprivacy %%%
    
Der Abschnitt könnte so lauten:

	## Eingebettete Inhalte

	Wenn Sie möchten, können Sie Inhalte von anderen Websites direkt bei
	uns darstellen lassen. Dabei werden Daten an diese Websites
	übermittelt. Es gelten die Datenschutzrichtlinien dieser Websites.
	Sie können hier selbst entscheiden, ob Sie diese Daten direkt auf
	unserer Website sehen möchten:

	%%% request embedprivacy %%%
