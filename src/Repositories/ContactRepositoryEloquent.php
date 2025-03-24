<?php

namespace Kwidoo\Contacts\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Kwidoo\Contacts\Contracts\ContactRepository;
use Kwidoo\Contacts\Models\Contact;

/**
 * Class ContactRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ContactRepositoryEloquent extends BaseRepository implements ContactRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return config('contacts.model'); //Contact::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * @param array $data
     *
     * @return Contact
     */
    public function createContact(array $data): Contact
    {
        $contact = $this->model->make($data);

        return $contact->writeable()->save() ? $contact : throw new Exception("Failed to save contact");
    }
}
