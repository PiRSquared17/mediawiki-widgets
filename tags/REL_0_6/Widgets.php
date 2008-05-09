<?php
/**
 *
 * {{#widget:<WidgetName>|<name1>=<value1>|<name2>=<value2>}}
 *
 * @author Sergey Chernyshev
 * @version $Id$
 */

$wgExtensionCredits['parserhook'][] = array(
        'name' => 'Widgets',
        'description' => 'Allows wiki administrators to add free-form widgets to wiki by just editing pages within Widget namespace. Originally developed for [http://www.ardorado.com Ardorado.com]',
	'version' => 0.6,
        'author' => '[http://www.sergeychernyshev.com Sergey Chernyshev] (for [http://www.semanticcommunities.com Semantic Communities LLC.])',
        'url' => 'http://www.mediawiki.org/wiki/Extension:Widgets'
);

// Initialize Smarty

require "$IP/extensions/Widgets/smarty/Smarty.class.php";
$smarty = new Smarty;
$smarty->left_delimiter = '<!--{';
$smarty->right_delimiter = '}-->';
$smarty->compile_dir  = "$IP/extensions/Widgets/compiled_templates/";
$smarty->security = true;
$smarty->security_settings = array(
	'IF_FUNCS' => array('is_array', 'isset', 'array', 'list', 'count', 'sizeof', 'in_array', 'true', 'false', 'null')
);

// Parser function registration
$wgExtensionFunctions[] = 'widgetParserFunctions';
$wgHooks['LanguageGetMagic'][] = 'widgetLanguageGetMagic';

// Init Widget namespaces
widgetNamespacesInit();

function widgetParserFunctions()
{
    global $wgParser;
    $wgParser->setFunctionHook('widget', 'renderWidget');
}

function widgetLanguageGetMagic( &$magicWords, $langCode = "en" )
{
	switch ( $langCode ) {
	default:
		$magicWords['widget']	= array ( 0, 'widget' );
	}
	return true;
}

function renderWidget (&$parser, $widgetName)
{
	global $smarty;

        $params = func_get_args();
        array_shift($params); # first one is parser - we don't need it
        array_shift($params); # second one is widget name

        foreach ($params as $param)
        {
                $pair = explode('=', $param, 2);

                if (isset($pair[1]))
                {
			$smarty->assign(trim($pair[0]), trim($pair[1]));
                }
        }

	try
	{
		$output = $smarty->fetch("wiki:$widgetName");
	}
	catch (Exception $e)
	{
		return "<div class=\"error\">Error in [[Widget:$widgetName]]</div>";
	}

	return array($output, 'noparse' => true, 'isHTML' => true, 'noargs' => true);
}

function widgetNamespacesInit() {
	global $widgetNamespaceIndex, $wgExtraNamespaces, $wgNamespacesWithSubpages,
			$wgGroupPermissions, $wgNamespaceProtection;

	if (!isset($widgetNamespaceIndex)) {
		$widgetNamespaceIndex = 274;
	}

	define('NS_WIDGET',       $widgetNamespaceIndex);
	define('NS_WIDGET_TALK',  $widgetNamespaceIndex+1);

	// Register namespace identifiers
	if (!is_array($wgExtraNamespaces)) { $wgExtraNamespaces=array(); }
	$wgExtraNamespaces = $wgExtraNamespaces + array(NS_WIDGET => 'Widget', NS_WIDGET_TALK => 'Widget_talk');

	// Support subpages only for talk pages by default
	$wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
		      NS_WIDGET_TALK => true
	);

	// Assign editing to 3idgeteditor group only (widgets can be dangerous so we do it here, not in LocalSettings)
	$wgGroupPermissions['*']['editwidgets'] = false;
	$wgGroupPermissions['widgeteditor']['editwidgets'] = true;

	// Setting required namespace permission rights
	$wgNamespaceProtection[NS_WIDGET] = array( 'editwidgets' );
}

// put these function somewhere in your application
function wiki_get_template ($widgetName, &$widgetCode, &$smarty_obj)
{
	$widgetTitle = Title::newFromText($widgetName, NS_WIDGET);
	if ($widgetTitle && $widgetTitle->exists())
	{
		$widgetArticle = new Article($widgetTitle);
		$widgetCode = $widgetArticle->getContent();

		// Remove <noinclude> sections and <includeonly> tags from form definition
		$widgetCode = StringUtils::delimiterReplace('<noinclude>', '</noinclude>', '', $widgetCode);
		$widgetCode = strtr($widgetCode, array('<includeonly>' => '', '</includeonly>' => ''));

		return true;
	}
	else
	{
		return false;
	}
}

function wiki_get_timestamp($widgetName, &$widgetTimestamp, &$smarty_obj)
{
	$widgetTitle = Title::newFromText($widgetName, NS_WIDGET);
	if ($widgetTitle && $widgetTitle->exists())
	{
		$widgetArticle = new Article($widgetTitle);
		$widgetTimestamp = $widgetArticle->getTouched();

		return true;
	}
	else
	{
		return false;
	}
}

function wiki_get_secure($tpl_name, &$smarty_obj)
{
    // assume all templates are secure
    return true;
}

function wiki_get_trusted($tpl_name, &$smarty_obj)
{
    // not used for templates
}

// register the resource name "db"
$smarty->register_resource("wiki", array("wiki_get_template",
                                       "wiki_get_timestamp",
                                       "wiki_get_secure",
                                       "wiki_get_trusted"));
