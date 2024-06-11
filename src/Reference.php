<?php

namespace Ashraf\LaravelTapPayment;

use InvalidArgumentException;
use Exception;

class Reference
{
    protected array $REQUIRED_CONFIG_VARS = ['secret_api_Key' => true];
    protected array $CONFIG_VARS = ['secret_api_Key' => null];
    protected array $CARD_VARS = [
        'number' => null, 'exp_month' => null, 'exp_year' => null, 'cvc' => null, 'name' => null, 'country' => null,
        'line1' => null, 'city' => null, 'street' => null, 'avenue' => null
    ];
    protected array $REQUIRED_CUSTOMER_VARS = ['name'];
    protected array $REQUIRED_CARD_VARS = [
        'number' => true, 'exp_month' => true, 'exp_year' => true, 'cvc' => true
    ];
    protected array $REQUIRED_CHARGE_VARS = [
        'customer' => [
            'first_name' => true, 'middle_name' => false, 'last_name' => false, 'email' => false,
            'phone' => [
                'country_code' => false, 'number' => false
            ]
        ],
        'address' => [
            'country' => false, 'city' => false, 'line1' => false, 'ip' => false
        ],
        'amount' => true, 'currency' => true, 'save_card' => false, 'threeDSecure' => true, 'description' => true,
        'statement_descriptor' => false,
        'metadata' => [
            'udf1' => false, 'udf2' => false
        ],
        'reference' => [
            'transaction' => false, 'order' => false
        ],
        'receipt' => [
            'email' => false, 'sms' => false
        ],
        'merchant' => [
            'id' => false
        ],
        'source' => [
            'id' => false
        ],
        'post' => [
            'url' => true
        ],
        'redirect' => [
            'url' => true
        ]
    ];
    protected array $CHARGE_VARS = [
        'customer' => [
            'first_name' => null, 'middle_name' => null, 'last_name' => null, 'email' => null,
            'phone' => [
                'country_code' => null, 'number' => null
            ]
        ],
        'address' => [
            'country' => null, 'city' => null, 'line1' => null, 'ip' => null
        ],
        'amount' => null, 'currency' => null, 'save_card' => 'false', 'description' => null, 'threeDSecure' => 'true',
        'statement_descriptor' => null,
        'metadata' => [
            'udf1' => null, 'udf2' => null
        ],
        'reference' => [
            'transaction' => null, 'order' => null
        ],
        'receipt' => [
            'email' => 'true', 'sms' => 'true'
        ],
        'merchant' => [
            'id' => null
        ],
        'source' => [
            'id' => null
        ],
        'post' => [
            'url' => null
        ],
        'redirect' => [
            'url' => null
        ]
    ];

    protected array $REFUND_VARS = [
        'charge_id' => null,
        'amount' => null,
        'currency' => null,
        'description' => null,
        'reason' => null,
        'reference' => [
            'merchant' => null
        ],
        'metadata' => [
            'udf1' => null,
            'udf2' => null,
        ],
        'post' => [
            'url' => null
        ]
    ];

    protected array $REQUIRED_REFUND_VARS = [
        'charge_id' => true,
        'amount' => true,
        'currency' => true,
        'description' => false,
        'reason' => true,
        'reference' => [
            'merchant' => false
        ],
        'metadata' => [
            'udf1' => false,
            'udf2' => false,
        ],
        'post' => [
            'url' => true
        ]
    ];

    protected array $CHARGES_FILTER = [
        'period' => [
            'date' => [
                'from' => null,
                'to' => null
            ]
        ],
        'status' => null,
        'limit' => 24
    ];

    protected array $REFUNDS_FILTER = [
        'period' => [
            'date' => [
                'from' => null,
                'to' => null
            ]
        ],
        'limit' => 24
    ];

    protected array $CHARGE_STATUS_LIST = [
        'INITIATED', 'ABANDONED', 'CANCELLED', 'FAILED', 'DECLINED', 'RESTRICTED', 'CAPTURED', 'VOID', 'TIMEDOUT', 'UNKNOWN'
    ];

    protected function cardValidator(array $data): void
    {
        foreach ($this->REQUIRED_CARD_VARS as $parm => $req_status) {
            if (array_key_exists($parm, $data)) {
                $this->CARD_VARS[$parm] = $data[$parm];
            } else {
                if ($req_status) {
                    // missing required parm
                    throw new InvalidArgumentException("InvalidArgumentException $parm field");
                }
            }
        }
    }

    protected function chargeValidator(array $data): void
    {
        foreach ($this->REQUIRED_CHARGE_VARS as $Firstkey => $req_status) {
            if (is_array($req_status)) {
                $SecondArray = $this->REQUIRED_CHARGE_VARS[$Firstkey];
                foreach ($SecondArray as $Secondkey => $req_status2) {
                    if (is_array($req_status2)) {
                        $ThirdArray = $this->REQUIRED_CHARGE_VARS[$Firstkey][$Secondkey];
                        foreach ($ThirdArray as $Thirdkey => $req_status3) {
                            if (isset($data[$Firstkey][$Secondkey]) && array_key_exists($Thirdkey, $data[$Firstkey][$Secondkey])) {
                                $this->CHARGE_VARS[$Firstkey][$Secondkey] = $data[$Firstkey][$Secondkey];
                            } else {
                                if ($req_status3) {
                                    // missing required parm
                                    throw new InvalidArgumentException("InvalidArgumentException $Firstkey.$Secondkey.$Thirdkey required");
                                } else {
                                    if (in_array($Thirdkey, ['country_code', 'number']) && $this->CHARGE_VARS[$Firstkey][$Secondkey][$Thirdkey] === null) {
                                        if (!isset($this->CHARGE_VARS['customer']['email']) || (isset($this->CHARGE_VARS['customer']['email']) && $this->CHARGE_VARS['customer']['email'] === null)) {
                                            throw new InvalidArgumentException("InvalidArgumentException $Firstkey.phone or $Firstkey.email is required");
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if (isset($data[$Firstkey]) && array_key_exists($Secondkey, $data[$Firstkey])) {
                            $this->CHARGE_VARS[$Firstkey][$Secondkey] = $data[$Firstkey][$Secondkey];
                        } else {
                            if ($req_status2) {
                                // missing required parm
                                throw new InvalidArgumentException("InvalidArgumentException $Firstkey.$Secondkey required");
                            }
                        }
                    }
                }
            } else {
                if (array_key_exists($Firstkey, $data)) {
                    $this->CHARGE_VARS[$Firstkey] = $data[$Firstkey];
                } else {
                    if ($req_status) {
                        // missing required parm
                        throw new InvalidArgumentException("InvalidArgumentException $Firstkey field");
                    }
                }
            }
        }
    }

    protected function refungValidator(array $data): void
    {
        foreach ($this->REQUIRED_REFUND_VARS as $Firstkey => $req_status) {
            if (is_array($req_status)) {
                $SecondArray = $this->REQUIRED_REFUND_VARS[$Firstkey];
                foreach ($SecondArray as $Secondkey => $req_status2) {
                    if (isset($data[$Firstkey]) && array_key_exists($Secondkey, $data[$Firstkey])) {
                        $this->REFUND_VARS[$Firstkey][$Secondkey] = $data[$Firstkey][$Secondkey];
                    } else {
                        if ($req_status2) {
                            // missing required parm
                            throw new InvalidArgumentException("InvalidArgumentException $Firstkey.$Secondkey required");
                        }
                    }
                }
            } else {
                if (array_key_exists($Firstkey, $data)) {
                    $this->REFUND_VARS[$Firstkey] = $data[$Firstkey];
                } else {
                    if ($req_status) {
                        // missing required parm
                        throw new InvalidArgumentException("InvalidArgumentException $Firstkey field");
                    }
                }
            }
        }
    }

    protected function chargesListValidator(array $options): void
    {
        if (isset($options['period'])) {
            if (isset($options['period']['date']['from'])) {
                $strtotime = strtotime($options['period']['date']['from']);
                if ($strtotime !== false && $strtotime > 0) {
                    $this->CHARGES_FILTER['period']['date']['from'] = $strtotime;
                } else {
                    throw new Exception("Exception period from date not valid!");
                }
            }

            if (isset($options['period']['date']['to'])) {
                $strtotime = strtotime($options['period']['date']['to']);
                if ($strtotime !== false && $strtotime > 0) {
                    $this->CHARGES_FILTER['period']['date']['to'] = $strtotime;
                } else {
                    throw new Exception("Exception period to date not valid!");
                }
            }
        }

        if (isset($options['status'])) {
            if (in_array($options['status'], $this->CHARGE_STATUS_LIST)) {
                $this->CHARGES_FILTER['status'] = $options['status'];
            } else {
                throw new Exception("Exception charge status not valid!");
            }
        }

        if (isset($options['limit'])) {
            if (is_numeric($options['limit']) && $options['limit'] > 0 && $options['limit'] < 51) {
                $this->CHARGES_FILTER['limit'] = $options['limit'];
            } else {
                throw new Exception("Exception charges limit not valid!");
            }
        }
    }

    protected function refundsListValidator(array $options): void
    {
        if (isset($options['period'])) {
            if (isset($options['period']['date']['from'])) {
                $strtotime = strtotime($options['period']['date']['from']);
                if ($strtotime !== false && $strtotime > 0) {
                    $this->REFUNDS_FILTER['period']['date']['from'] = $strtotime;
                } else {
                    throw new Exception("Exception period from date not valid!");
                }
            }

            if (isset($options['period']['date']['to'])) {
                $strtotime = strtotime($options['period']['date']['to']);
                if ($strtotime !== false && $strtotime > 0) {
                    $this->REFUNDS_FILTER['period']['date']['to'] = $strtotime;
                } else {
                    throw new Exception("Exception period to date not valid!");
                }
            }
        }

        if (isset($options['limit'])) {
            if (is_numeric($options['limit']) && $options['limit'] > 0 && $options['limit'] < 51) {
                $this->REFUNDS_FILTER['limit'] = $options['limit'];
            } else {
                throw new Exception("Exception refunds limit not valid!");
            }
        }
    }
}
