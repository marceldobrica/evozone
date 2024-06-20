<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CnpValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Cnp) {
            throw new UnexpectedTypeException($constraint, Cnp::class);
        }

        preg_match(
            '/^([1-8])(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(0[1-9]|[1-4]\d|5[1-2]|99)(\d{3})(\d)$/',
            $value,
            $matches
        );

        /*
         * Validarea unui C.N.P. constă în calcularea componentei C și compararea acesteia cu valoarea primită a
         * aceleiași componente. Dacă acestea sunt identice, înseamnă că C.N.P. verificat este valid.
         * Calcularea componentei C se face folosind constanta "279146358279", după cum urmează:
         * fiecare cifră din primele 12 cifre ale C.N.P. este înmulțită cu corespondentul său din constantă
         * rezultate sunt însumate și totalul se împarte la 11
         * dacă restul împărțirii este mai mic de 10, acela reprezintă valoarea componentei C
         * dacă restul împărțirii este 10, valoarea componentei C este 1
         */
        if (isset($matches[0])) {
            $compoentaControl = "279146358279";
            $cnpArray = array_map('intval', str_split($matches[0]));
            $compoentaControlArray = array_map('intval', str_split($compoentaControl));
            $controlSum = 0;
            foreach ($compoentaControlArray as $key => $value) {
                $controlSum += $value * $cnpArray[$key];
            }
            $rest = $controlSum % 11;
            $cComponent = 0;
            if ($rest < 10) {
                $cComponent = $rest;
            }
            if (10 == $rest) {
                $cComponent = 1;
            }
            if (intval($matches[7]) === $cComponent) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
