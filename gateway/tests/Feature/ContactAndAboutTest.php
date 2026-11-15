<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAndAboutTest extends TestCase
{
    public function test_homepage_assistance_link_opens_the_contact_page(): void
    {
        Http::fake([
            '*' => Http::response([
                'utilisateurs_inscrits' => 0,
                'medicaments_catalogue' => 0,
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Contacter l’assistance"', false)
            ->assertSee('href="'.route('contact').'"', false);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('href="tel:+22893123456"', false);
    }

    public function test_about_and_contact_pages_are_available_and_linked(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('À propos de nous')
            ->assertSee(route('contact'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Contactez-nous')
            ->assertSee('name="message"', false)
            ->assertSee(route('contact.send'));
    }

    public function test_contact_form_emails_the_configured_inbox_and_allows_a_reply(): void
    {
        Mail::fake();
        config(['services.contact.email' => 'support@example.com']);

        $this->post(route('contact.send'), [
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'subject' => 'Question sur ma commande',
            'message' => 'Bonjour, pouvez-vous m’aider avec ma commande ?',
        ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            return $mail->hasTo('support@example.com')
                && $mail->senderName === 'Ama Mensah'
                && $mail->senderEmail === 'ama@example.com'
                && $mail->topic === 'Question sur ma commande'
                && $mail->body === 'Bonjour, pouvez-vous m’aider avec ma commande ?';
        });
    }

    public function test_contact_form_rejects_invalid_data_without_sending_email(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->from(route('contact'))->post(route('contact.send'), [
                'name' => '',
                'email' => 'not-an-email',
                'subject' => '',
                'message' => 'court',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }

    public function test_storefront_product_pages_no_longer_render_medicine_images(): void
    {
        config(['services.public_stats.catalog_url' => 'http://catalog']);

        Http::fake([
            'http://catalog/api/categories' => Http::response(['data' => []]),
            'http://catalog/api/medicaments/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'categorie_id' => 1,
                    'categorie' => ['libelle' => 'Médicaments généraux'],
                    'nom' => 'Doliprane',
                    'prix' => 2500,
                    'quantite_stock' => 10,
                    'ordonnance_requise' => false,
                    'description' => 'Produit de test.',
                ],
            ]),
            'http://catalog/api/medicaments*' => Http::response([
                'data' => ['data' => [], 'current_page' => 1, 'last_page' => 1],
            ]),
            '*' => Http::response([
                'utilisateurs_inscrits' => 0,
                'medicaments_catalogue' => 0,
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('images/product-', false)
            ->assertDontSee('promo-capsules.svg', false);

        $this->get(route('catalogue'))
            ->assertOk()
            ->assertDontSee('images/product-', false)
            ->assertDontSee('Boîte de', false);

        $this->get(route('medicament.show', ['id' => 1]))
            ->assertOk()
            ->assertDontSee('images/product-', false)
            ->assertDontSee('Boîte de', false);
    }
}
