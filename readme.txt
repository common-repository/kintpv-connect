=== Kintpv Wooconnect ===
Contributors: kinhelios
Tags: woocommerce, kintpv, connect, store, synchronisation, boutique, kinhelios, tpv, caisse, magasin, stock, produit, commerce, article, gestion
Requires at least: 5.0
Tested up to: 6.6.2
Requires PHP: 7.3
Stable tag: 8.129

Synchronisez le logciel de caisse KinTPV avec votre boutique WooCommerce


== Description ==
KinTPV est un logiciel de caisse. Il dispose de toutes les interfaces nécessaires pour vendre, gérer, analyser et dynamiser votre activité en toute tranquillité. 

KinTPV s’installe sur votre ordinateur Mac ou Windows.

KinTPV s'interface aisément avec votre boutique e-commerce et notamment avec WooCommerce grâce au module "KinTPV Wooconnect".

L’extension "KinTPV Wooconnect" vous fait gagner du temps, de la fiabilité et simplifie la gestion de votre commerce en ligne.

Les articles et les stocks présents dans KinTPV sont automatiquement recopiés sur votre site WooCommerce.

Les photos des articles sont aussi envoyées sur le site.

Toute modification de stock est répercutée sur votre boutique WooCommerce.

L’organisation des produits par catégorie se fait dans KinTPV et est transmise à votre boutique en ligne.

Les commandes passées sur votre boutique sont récupérées par KinTPV afin d'y être enregistrées pour mettre à jour le stock et le chiffre d'affaires.

== Installation ==
La configuration de l\'extension KinTPV WooConnect est diponible sur la documentation de KinTPV à l\'adresse suivante :
https://wiki.kintpv.com/doku.php?id=tutoriel:install-extension-woocommerce

== Changelog ==

= 8.129 =
* Correction de l'import de déclinaisons lorsqu'il y a un '/' dans le nom.

= 8.128 =
* Ajout d'une configuration entre les taxes WooCommerce et KinTPV séparée pour la récupération des commandes.

= 8.123 =
* Ajout d'une option dans la configuration du module permettant d'ignorer la récupération des commandes ayant le statut "Terminée".
* Correction de la récupération des taux de TVA qui ne retorunait que les 10 premiers taux.
* Ajout du nom du taux de TVA dans la liste de sélection.

= 8.120 =
* Correction des produits toujours visibles dans le catalogue après avoir été décoché "publier web" sur KinTPV.

= 8.119 =
* Modification de la gestion de l'enregistrement des options du module.
* Amélioration de la mise à jour des produits permettant dans certains cas spéficiques de transformer un produit variable en produit simple.

= 8.118 =
* Ajout d'informations dans le fichier de logs lors de la mise à jour des préférences du module.

= 8.116 =
* Précision du message "API non disponible" lorsque la préférence des permaliens est configurée par défaut.

= 8.115 =
* Correction de la création d'un article en état "Brouillon" si un article décoché web et/ou supprimé est envoyé par KinTPV.

= 8.114 =
* Correction d'une traduction dans la page de configuration.
* Correction des images pouvant être dupliquées et mal affichées sur le front-office de la boutique.

= 8.112 =
* Correction de la page de configuration de l'extension affichant de mauvaises valeurs.

= 8.111 =
* Ajout de l'information dans le log de l'emplacement final d'une image importée.
* Précision du nom du produit dans le fichier de log à l'import d'une image.

= 8.110 =
* Ajout de l'information de la taille du fichier de logs dans la page de configuration de l'extension.
* Correction du fichier de logs impossible à supprimer dans la configuration de l'extension.
* Correction de l'état de publication d'un nouveau produit défini à "publié" sans prendre en compte la configuration de l'extension.

= 8.109 =
* Compatibilité avec le magasin d'extensions de Wordpress

= 8.107 =
* Ajout de l\'option \"Les produits hors stocks sont virtuels\" dans configuration du module.
* L\'état \"hors stock\" des produits sur KinTPV désactive la gestion du stock en conservant un statut \"en stock\".
* Modification de la gestion de l\'id de recherche des commandes et retours.

= 8.104 =
* Optimisation de la gestion d\'import des produits et déclinaisons.
* L\'option \'Autoriser les commandes si rupture de stocks\' est maintenant correctement prise en compte.

= 8.098 =
* Amélioration de la récupération des commandes retournées
* Produits : Correction de l\'image principale importée aux produits

= 8.097 =
* Correction de l\'id de récupération des retours non pris en compte

= 8.096 =
* Correction de la récupération des détails d\'une commande remboursée

= 8.090 =
* Correction de l\'import des images produit

= 8.089 =
* Correction de l\'affichage des transporteurs
* Correction de la récupération des frais d\'expédition dans KinTPV

= 8.088 =
* Ajout d\'une limite du nombre de commandes récupérées par synchronisation, définie dans la page de configuration du module
* Correction d\'une erreur à l\'import des images sur les serveurs PHP ayant une version inférieure à 8.0

= 8.087 =
* Optimisation de la vitesse de la récupération des commandes vers KinTPV
* Correction d\'une erreur dans la recherche des déclinaisons devant être mises à jour

= 8.081 =
* Optimisation de la vitesse d\'import des articles dans WooCommerce
* Correction d\'une erreur lorsque KinTPV envoie des images de critères à WooCommerce

= 8.074 =
* Correction d\'une erreur dans l\'XML retourné à KinTPV

= 8.073 =
* Nouvelle amélioration de la vérification de connexion avec KinTPV 

= 8.068 =
* Correction des déclinaisons désactivées à la mise à jour d\'un produit

= 8.067 =
* Correction de l\'import des images des catégories

= 8.066 =
* Correction d\'un chemin d\'appel empêchant l\'import des images
* Correction d\'une erreur affichée dans la configuration du module lorsque les modes de paiement kintpv n\'ont pas encore été envoyés 

= 8.065 =
* Amélioration de la vérification de connexion à KinTPV ajoutée en 8.060
* Correction de l\'impossibilité de mettre à jour un produit \"simple\" de KinTPV dans une déclinaison sur WooCommerce

= 8.063 =
* Correction de la récupération des retours si la date envoyée est nulle (0000-00-00T00:00:00)

= 8.062 =
* Correction d\'une erreur de récupération si la date de la dernière commande envoyée par KinTPV est vide.

= 8.061 =
* Correction lors de la suppression d\'un produit dans KinTPV n\'ayant jamais été publié web, qui entraînait sa création dans WooCommerce

= 8.060 = 
* Ajout d\'une vérification régulière de connexion avec KinTPV pour éviter l\'arrêt de l\'envoi des articles
* Correction d\'une erreur dans l\'XML de retour lorsque l\'option \"Request 4D\" est cochée dans KinTPV

= 8.057 =
* Ajout de la gestion du choix des états de commandes renvoyant un état web validé à KinTPV
* Ajout de la gestion de récupération des commandes et retours de commande par date
* Les noms des types de paiement sont désormais listés par ordre alphabétique
* Les fixhiers XML sont désormais triés correctement
* Le nom des critères dans le tableau de configuration affiche désormais le nom des critères de KinTPV

= 8.054 =
* Réécriture de la fonction permettant de générer l\'url simplifiée (slug) des attributs

= 8.052 =
* Correction d\'une erreur retournée à la mise à jour d\'une catégorie

= 8.051 =
* Ajout d\'un message retourné à KinTPV lorsqu\'un nom de catégorie ou de déclinaison est trop long
* Correction de l\'import de produits déclinés qui supprimait les images des déclinaisons

= 8.050 =
* Correction d\'une erreur d\'import des attributs causée par certains caractères dans les noms

= 8.047 =
* Ajout d\'une option permettant de vider le fichier de logs

= 8.045 =
* Correction d\'une erreur de création d\'attributs causée par le nom dans certains cas 
* Correction de la visibilité du catalogue définie systématiquement à \"cachée\" lors de l\'import des produits

= 8.040 =
* Désormais, les attributs et critères sont correctement enregistrés en attributs \"globaux\" dans WooCommerce
* Correction de l\'option \"État à la création\" qui était prise en compte à la modification
* Amélioration de la gestion de l\'erreur \"Id non valide\" lors de la récupération des ventes web

= 8.036 =
* Correction des promotions qui n\'étaient pas supprimées lorsqu\'elles l\'étaient dans KinTPV

= 8.035 =
* Les étiquettes d\'un produit ne sont plus supprimées lors de la mise à jour depuis KinTPV
* Ajout d\'une option dans les préférences du module pour activer ou non l\'import des \'Meta keyword\' dans les étiquettes produit
* L\'option \"Etat à la création\" dans la configuration est désormais correctement prise en compte lors de l\'import de produits depuis KinTPV

= 8.033 =
* Les fichiers XMl terminés et abandonnés sont désormais triés du plus récent au plus ancien dans la page de configuration du module
* Correction de l\'erreur \"ID non valide\" pouvant être retournée à KinTPV

= 8.031 =
* Correction de la création et attribution des catégories lorsque des accents son présents dans l\'URL simplifiée
* Correction de la gestion des catégories Mères/Filles (sous-catégories)

= 8.027 =
* Ajout des mots clés KinTPV (Meta Keyword) en tant qu\'étiquettes produit
* Correction de l\'attribution des catégories à un article lors de l\'import depuis KinTPV qui ne se faisait plus
* Modification de la gestion de l\'affichage des cases à cocher pour la création/modification

= 8.021 =
* L\'url simplifiée de la catégorie est désormais utilisée à la place de son id lorsque les url des produits contiennent les catégories
* Affichage de l\'id des transporteurs dans la configuration du module
* La récupération des ventes web prend désormais moins de temps, empêchant une erreur de réception dans KinTPV
* Modification du traitement pour ne plus envoyer à KinTPV les commandes qu\'il a déjà reçu
* Correction du code client envoyé à KinTPV si la commande a été passée en tant qu\'invité 
* Correction de l\'envoi du prix unitaire à KinTPV
* Correction des promotions sans date de fin envoyées par KinTPV qui n\'étaient pas prises en compte dans WooCommerce.
* Correction de l\'url du produit qui affichait sa référence si l\'option \"url simplifiée\" n\'était pas activée dans les options du module