# Social Bulk Invite
This module creates an Invite-by-email widget that is visible only by the members of a chosen group


<br><br>

## Big Issue, for now!!

As it stands right now, you must add this bit of code to the end of the file:
```
protected/humhub/components/console/Application.php
```
just ***above*** the last curly bracket (line 76 in HumHub 1.17.1)
```
    // begin added by me
    public function setLanguage($value)
    {
        if (!empty($value)) {
            $this->language = $value;
        }
    }
    // end added by me
```

<br><br>

## Donationware -- Consider a Donation!!

Your doantion would really, really, really, really help!  
https://www.paypal.com/donate/?hosted_button_id=AEA7Q4V5RMY4S

Thank You!

<br><br>

## Other ways to install

### Installation (Using Git Clone)

- Clone the social_bulk_invite module into your modules directory
```
cd protected/modules
git clone https://github.com/petersphilo/humhub-social_bulk_invite.git social_bulk_invite
```

- Go to Admin > Modules. You should now see the `Social Bulk Invite` module in your list of installed modules

- Click "Enable". This will install the module for you

Eventually, i hope to have this module in the 'store'

### Installation (Manually, using Release zip - for those not comfortable with the command line)

- Download the zip file from [/releases/latest](https://github.com/petersphilo/humhub-social_bulk_invite/releases/latest)

- Upload it to the `protected/modules` directory of your HumHub installation and expand it (then delete the zip file)

- Go to Admin > Modules. You should now see the `Social Bulk Invite` module in your list of installed modules

- Click "Enable". This will install the module for you

Eventually, i hope to have this module in the 'store'