const submit = document.getElementById('sendseed');
const inputseed1 = document.getElementById('seed1');
const iconseed1 = document.getElementById('testseed1');
const inputseed2 = document.getElementById('seed2');
const iconseed2 = document.getElementById('testseed2');
const inputseed3 = document.getElementById('seed3');
const iconseed3 = document.getElementById('testseed3');
const inputseed4 = document.getElementById('seed4');
const iconseed4 = document.getElementById('testseed4');
var seed1Validated = false;
var seed2Validated = false;
var seed3Validated = false;
var seed4Validated = false;

const MAXCHARNAMEFIELD = 15;
const MINCHARNAMEFIELD = 3;

//https://www.ma-no.org/en/programming/javascript/validating-html-
//forms-using-bulma-and-vanilla-javascript
// EVENT LISTENERS
document.addEventListener('change', event => {

  if (event.target.matches('.inputSeed1')) {
    validateSeed1()
  } else if (event.target.matches('.inputSeed2')) {
    validateSeed2()
  } else if (event.target.matches('.inputSeed3')) {
    validateSeed3()
  } else if (event.target.matches('.inputSeed4')) {
    validateSeed4()
  }  else {
    //nothing
  }
}, false)

function validateSeed1() {

  const iconseed1 = document.getElementById('testseed1')
  const conditions =
    (inputseed1.value.length > MINCHARNAMEFIELD) &&
    (inputseed1.value.length < MAXCHARNAMEFIELD) &&
    (inputseed1.value != null)

  if (conditions) {
    // input box color
    inputseed1.classList.remove('is-danger')
    inputseed1.classList.add('is-success')
    // icon type
    iconseed1.classList.remove('fa-exclamation-triangle')
    iconseed1.classList.add('fa-check')
    // console.log("icon :" + icon.classList.value)
    //now we call submit button test
    seed1Validated = true
    submitCheck()
  } else {
    // input box color
    inputseed1.classList.remove('is-sucess')
    inputseed1.classList.add('is-danger')
    // icon type
    iconseed1.classList.remove('fa-check')
    iconseed1.classList.add('fa-exclamation-triangle')
    seed1Validated = false
  }
}

function validateSeed2() {

  const iconseed2 = document.getElementById('testseed2')
  const conditions =
    (inputseed2.value.length > MINCHARNAMEFIELD) &&
    (inputseed2.value.length < MAXCHARNAMEFIELD) &&
    (inputseed2.value != null)

  if (conditions) {
    // input box color
    inputseed2.classList.remove('is-danger')
    inputseed2.classList.add('is-success')
    // icon type
    iconseed2.classList.remove('fa-exclamation-triangle')
    iconseed2.classList.add('fa-check')
    // console.log("icon :" + icon.classList.value)
    //now we call submit button test
    seed2Validated = true
    submitCheck()
  } else {
    // input box color
    inputseed2.classList.remove('is-sucess')
    inputseed2.classList.add('is-danger')
    // icon type
    iconseed2.classList.remove('fa-check')
    iconseed2.classList.add('fa-exclamation-triangle')
    seed2Validated = false
  }
}

function validateSeed3() {

  const iconseed3 = document.getElementById('testseed3')
  const conditions =
    (inputseed3.value.length > MINCHARNAMEFIELD) &&
    (inputseed3.value.length < MAXCHARNAMEFIELD) &&
    (inputseed3.value != null)

  if (conditions) {
    // input box color
    inputseed3.classList.remove('is-danger')
    inputseed3.classList.add('is-success')
    // icon type
    iconseed3.classList.remove('fa-exclamation-triangle')
    iconseed3.classList.add('fa-check')
    // console.log("icon :" + icon.classList.value)
    //now we call submit button test
    seed3Validated = true
    submitCheck()
  } else {
    // input box color
    inputseed3.classList.remove('is-sucess')
    inputseed3.classList.add('is-danger')
    // icon type
    iconseed3.classList.remove('fa-check')
    iconseed3.classList.add('fa-exclamation-triangle')
    seed3Validated = false
  }
}

function validateSeed4() {

  const iconseed4 = document.getElementById('testseed4')
  const conditions =
    (inputseed4.value.length > MINCHARNAMEFIELD) &&
    (inputseed4.value.length < MAXCHARNAMEFIELD) &&
    (inputseed4.value != null)

  if (conditions) {
    // input box color
    inputseed4.classList.remove('is-danger')
    inputseed4.classList.add('is-success')
    // icon type
    iconseed4.classList.remove('fa-exclamation-triangle')
    iconseed4.classList.add('fa-check')
    // console.log("icon :" + icon.classList.value)
    //now we call submit button test
    seed4Validated = true
    submitCheck()
  } else {
    // input box color
    inputseed4.classList.remove('is-sucess')
    inputseed4.classList.add('is-danger')
    // icon type
    iconseed4.classList.remove('fa-check')
    iconseed4.classList.add('fa-exclamation-triangle')
    seed4Validated = false
  }
}

function submitCheck() {
  if (seed1Validated && seed2Validated && seed3Validated && seed4Validated) {
    submit.disabled = false;              //button is no longer no-clickable
    submit.removeAttribute("disabled");   //detto
  }else{
    submit.disabled = true; 
  }
}
