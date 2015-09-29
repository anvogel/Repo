<?php
	/* takes the input, scrubs bad characters */
function generate_seo_link($input, $replace = '-', $remove_words = true, $words_array = array()) {
	//make it lowercase, remove punctuation, remove multiple/leading/ending spaces
	$return = trim(ereg_replace(' +', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($input))));

	//remove words, if not helpful to seo
	//i like my defaults list in remove_words(), so I wont pass that array
	if($remove_words) { $return = remove_words($return, $replace, $words_array); }

	//convert the spaces to whatever the user wants
	//usually a dash or underscore..
	//...then return the value.
	return str_replace(' ', $replace, $return);
}

/* takes an input, scrubs unnecessary words */
function remove_words($input,$replace,$words_array = array(),$unique_words = true)
{
	//separate all words based on spaces
	$input_array = explode(' ',$input);

	//create the return array
	$return = array();

	//loops through words, remove bad words, keep good ones
	foreach($input_array as $word)
	{
		//if it's a word we should add...
		if(!in_array($word,$words_array) && ($unique_words ? !in_array($word,$return) : true))
		{
			$return[] = $word;
		}
	}

	//return good words separated by dashes
	return implode($replace,$return);
}

$bad_words = array('a','and','the','an','it','is','with','can','of','why','not');
echo generate_seo_link('Another day and a half of PHP meetings änder über', '-', true, $bad_words);
//displays :: another-day-half-php-meetings
echo "<br>";
echo generate_seo_link('CSS again?  Why not just PHP?', '-', true, $bad_words);
//displays :: css-again-just-php
echo "<br>";
echo generate_seo_link('A penny saved is a penny earned.', '-', true, $bad_words);
//displays :: penny-saved-earned
?>