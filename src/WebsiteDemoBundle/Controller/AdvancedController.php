<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Model\Asset;
use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Pimcore\Model\Object;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Zend\Paginator\Paginator;

class AdvancedController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        $this->enableLayout('WebsiteDemoBundle::layout.phtml');
    }

    public function contactFormAction(Request $request)
    {
        $success = false;

        if ($request->get("provider")) {
            $adapter = Tool\HybridAuth::authenticate($request->get("provider"));
            if ($adapter) {
                $user_data = $adapter->getUserProfile();
                if ($user_data) {
                    $this->setParam("firstname", $user_data->firstName);
                    $this->setParam("lastname", $user_data->lastName);
                    $this->setParam("email", $user_data->email);
                    $this->setParam("gender", $user_data->gender);
                }
            }
        }

        // getting parameters is very easy ... just call $request->get("yorParamKey"); regardless if's POST or GET
        if ($request->get("firstname") && $request->get("lastname") && $request->get("email") && $request->get("message")) {
            $success = true;

            $mail = new Mail();
            $mail->setIgnoreDebugMode(true);

            // To is used from the email document, but can also be set manually here (same for subject, CC, BCC, ...)
            //$mail->addTo("info@pimcore.org");

            $emailDocument = $this->document->getProperty("email");
            if (!$emailDocument) {
                $emailDocument = Document::getById(38);
            }

            $mail->setDocument($emailDocument);
            $mail->setParams($this->getAllParams());
            $mail->send();
        }

        // do some validation & assign the parameters to the view
        foreach (["firstname", "lastname", "email", "message", "gender"] as $key) {
            if ($request->get($key)) {
                $this->view->$key = htmlentities(strip_tags($request->get($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    public function searchAction(Request $request)
    {
        if ($request->get("q")) {
            try {
                $page = $request->get('page');
                if (empty($page)) {
                    $page = 1;
                }
                $perPage = 10;

                $result = \Pimcore\Google\Cse::search($request->get("q"), (($page - 1) * $perPage), null, [
                    "cx" => "002859715628130885299:baocppu9mii"
                ], $request->get("facet"));

                $paginator = new Paginator($result);
                $paginator->setCurrentPageNumber($page);
                $paginator->setItemCountPerPage($perPage);
                $this->view->paginator = $paginator;
                $this->view->result = $result;
            } catch (\Exception $e) {
                // something went wrong: eg. limit exceeded, wrong configuration, ...
                \Pimcore\Logger::err($e);
                echo $e->getMessage();
                exit;
            }
        }
    }

    public function objectFormAction(Request $request)
    {
        $success = false;

        // getting parameters is very easy ... just call $request->get("yorParamKey"); regardless if's POST or GET
        if ($request->get("firstname") && $request->get("lastname") && $request->get("email") && $request->get("terms")) {
            $success = true;

            // for this example the class "person" and "inquiry" is used
            // first we create a person, then we create an inquiry object and link them together

            // check for an existing person with this name
            $person = Object\Person::getByEmail($request->get("email"), 1);

            if (!$person) {
                // if there isn't an existing, ... create one
                $filename = \Pimcore\File::getValidFilename($request->get("email"));

                // first we need to create a new object, and fill some system-related information
                $person = new Object\Person();
                $person->setParent(Object\AbstractObject::getByPath("/crm/inquiries")); // we store all objects in /crm
                $person->setKey($filename); // the filename of the object
                $person->setPublished(true); // yep, it should be published :)

                // of course this needs some validation here in production...
                $person->setGender($request->get("gender"));
                $person->setFirstname($request->get("firstname"));
                $person->setLastname($request->get("lastname"));
                $person->setEmail($request->get("email"));
                $person->setDateRegister(new \DateTime());
                $person->save();
            }

            // now we create the inquiry object and link the person in it
            $inquiryFilename = \Pimcore\File::getValidFilename(date("Y-m-d") . "~" . $person->getEmail());
            $inquiry = new Object\Inquiry();
            $inquiry->setParent(Object\AbstractObject::getByPath("/inquiries")); // we store all objects in /inquiries
            $inquiry->setKey($inquiryFilename); // the filename of the object
            $inquiry->setPublished(true); // yep, it should be published :)

            // now we fill in the data
            $inquiry->setMessage($request->get("message"));
            $inquiry->setPerson($person);
            $inquiry->setDate(new \DateTime());
            $inquiry->setTerms((bool) $request->get("terms"));
            $inquiry->save();
        } elseif ($request->isMethod('POST')) {
            $this->view->error = true;
        }

        // do some validation & assign the parameters to the view
        foreach (["firstname", "lastname", "email", "message", "terms"] as $key) {
            if ($request->get($key)) {
                $this->view->$key = htmlentities(strip_tags($request->get($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    public function sitemapAction(Request $request)
    {
        set_time_limit(900);

        $this->view->initial = false;

        if ($request->get("doc")) {
            $doc = $request->get("doc");
            $this->disableLayout();
        } else {
            $doc = $this->document->getProperty("mainNavStartNode");
            $this->view->initial = true;
        }

        \Pimcore::collectGarbage();

        $this->view->doc = $doc;
    }

    public function assetThumbnailListAction()
    {

        // try to get the tag where the parent folder is specified
        $parentFolder = $this->document->getElement("parentFolder");
        if ($parentFolder) {
            $parentFolder = $parentFolder->getElement();
        }

        if (!$parentFolder) {
            // default is the home folder
            $parentFolder = Asset::getById(1);
        }

        // get all children of the parent
        $list = new Asset\Listing();
        $list->setCondition("path like ?", $parentFolder->getFullpath() . "%");

        $this->view->list = $list;


    }

}