# electionhelpline
Build election Helpline using Exotel, Google and MapBox APIs

This repo is a good example of how you can build some powerful apps using Exotel and Google/MapBox APIs.
Here we have set up easy helpline for the Indian elections where a caller can get the details of the 
candidates that are contesting from a constituency, given their pincode(zipcode)


Setup
Setup a Exotel CallFlow with the following Applets

Gather -> Passthru

The Gather Applet would collect the Pincode and pass it on to the Passthru endpoint.

You can set up electionshelpline.php as the handler for the passthru and you are set