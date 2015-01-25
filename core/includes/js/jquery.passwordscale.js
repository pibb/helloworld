// Password strength meter
// This jQuery plugin is written by firas kassem [2007.04.05] and modified by michael shelton [2011]
// Firas Kassem  phiras.wordpress.com || phiras at gmail {dot} com
// for more information : http://phiras.wordpress.com/2007/04/08/password-strength-meter-a-jquery-plugin/

var shortPass 		= "Too Short";
var badPass 		= "Bad";
var goodPass 		= "Good";
var strongPass 		= "Strong";
var noSymbols 		= "No Special Characters";


function passwordStrength(password)
{
    score = 0 
    
    //password < 4
    if (password.length < 3 ) return shortPass;
    
    //password length
    score += password.length * 4;
    score += ( checkRepetition(1,password).length - password.length ) * 1;
    score += ( checkRepetition(2,password).length - password.length ) * 1;
    score += ( checkRepetition(3,password).length - password.length ) * 1;
    score += ( checkRepetition(4,password).length - password.length ) * 1;

    //password has 3 numbers
    if (password.match(/(.*[0-9].*[0-9].*[0-9])/)) score += 5 ;

    //password has Upper and Lower chars
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) score += 10 ;

    //password has number and chars
    if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) score += 15;

    //password has symbols
    if (!password.match(/^[A-Za-z0-9_]+$/)) return noSymbols;

    //password is just a numbers or chars
    if (password.match(/^\w+$/) || password.match(/^\d+$/) ) score -= password.length;

    if (score < 34 ) return badPass;
    if (score < 68 ) return goodPass;
	
    return strongPass;
}

function checkRepetition(pLen,str) {
    res = ""
    for ( i=0; i<str.length ; i++ ) {
        repeated=true
        for (j=0;j < pLen && (j+i+pLen) < str.length;j++)
            repeated=repeated && (str.charAt(j+i)==str.charAt(j+i+pLen))
        if (j<pLen) repeated=false
        if (repeated) {
            i+=pLen-1
            repeated=false
        }
        else {
            res+=str.charAt(i)
        }
    }
    return res
}