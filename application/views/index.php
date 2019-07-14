<!DOCTYPE html>
<html>
<head>
    <title>Codeigniter - Paypal Integration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
    body {
        font-family: Arial;
        color: #212121;
    }

    .fa-linkedin {
        background: #007bb5;
        color: white;
    }

    .fa {
        padding: 20px;
        font-size: 30px;
        width: 50px;
        text-align: center;
        text-decoration: none;
        margin: 5px 2px;
    }

    #subscription-plan {
        padding: 20px;
        border: #E0E0E0 2px solid;
        text-align: center;
        width: 200px;
        border-radius: 3px;
        margin: 40px auto;
    }

    .plan-info {
        font-size: 1em;
    }

    .plan-desc {
        margin: 10px 0px 20px 0px;
        color: #a3a3a3;
        font-size: 0.95em;
    }

    .price {
        font-size: 1.5em;
        padding: 30px 0px;
        border-top: #f3f1f1 1px solid;
    }

    .btn-subscribe {
        padding: 10px;
        background: #e2bf56;
        width: 100%;
        border-radius: 3px;
        border: #d4b759 1px solid;
        font-size: 0.95em;
    }
    </style>
</head>

<body>
    <div id="subscription-plan">
        <div class="plan-info">Paypal subscription</div>
        <div class="plan-desc">Paypal subscription tutorial using Codeigniter</div>
        <div class="price">$49 / month</div>

        <div>
            <form action="<?= base_url() ?>index.php/payment/subscribe" method="post">
                <input type="hidden" name="plan_name" value="PHP jQuery Tutorials" /> 
                <input type="hidden" name="plan_description" value="Tutorials access to learn PHP with simple examples." />
                <input type="submit" name="subscribe" value="Subscribe" class="btn-subscribe" />
            </form>
        </div>
    </div>

    <div id="subscription-plan">
        <div class="plan-info">Paypal Payment</div>
        <div class="plan-desc">Paypal payment checkout tutorial using Codeigniter</div>
        <div class="price">$10</div>

        <div>
            <form action="<?= base_url() ?>index.php/payment/create_payment" method="post">
                <input type="hidden" name="plan_name" value="PHP jQuery Tutorials" /> 
                <input type="hidden" name="plan_description" value="Tutorials access to learn PHP with simple examples." />
                <input type="submit" name="subscribe" value="Checkout" class="btn-subscribe" />
            </form>
        </div>
    </div>

    <div align="center">
        <a href="https://bd.linkedin.com/in/knrahman" class="fa fa-linkedin" target="_blank"></a>
    </div>
</body>

</html>

