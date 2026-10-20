<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PropertyUploadTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $agency;

    private User $agentUser;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Storage::fake('public');

        $this->agency = Agency::factory()->create();
        $this->agentUser = $this->makeAgent($this->agency);
        $this->property = Property::factory()->create([
            'agency_id' => $this->agency->id,
            'agent_id' => $this->agentUser->agent->id,
        ]);
    }

    private function jpg(string $name = 'photo.jpg', int $kb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'image/jpeg');
    }

    private function upload(array $files, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->agentUser)
            ->postJson("/api/properties/{$this->property->id}/images", ['images' => $files]);
    }

    public function test_valid_images_are_stored_and_first_becomes_primary(): void
    {
        $res = $this->upload([$this->jpg('a.jpg'), UploadedFile::fake()->create('b.png', 50, 'image/png')])
            ->assertStatus(201);

        $this->assertCount(2, $res->json('data'));
        $this->assertSame(2, PropertyImage::where('property_id', $this->property->id)->count());
        $this->assertTrue((bool) PropertyImage::orderBy('id')->first()->is_primary);
        foreach (PropertyImage::all() as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_non_image_mime_is_rejected(): void
    {
        $this->upload([UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('images.0');
        $this->upload([UploadedFile::fake()->create('evil.php', 5, 'application/x-php')])
            ->assertStatus(422);
        $this->upload([UploadedFile::fake()->create('anim.gif', 5, 'image/gif')])
            ->assertStatus(422);

        $this->assertSame(0, PropertyImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_oversize_image_is_rejected(): void
    {
        $this->upload([$this->jpg('big.jpg', 5121)])->assertStatus(422)->assertJsonValidationErrors('images.0');
        $this->upload([$this->jpg('ok.jpg', 5120)])->assertStatus(201);
    }

    public function test_more_than_ten_files_are_rejected(): void
    {
        $files = array_map(fn ($i) => $this->jpg("p{$i}.jpg", 10), range(1, 11));

        $this->upload($files)->assertStatus(422)->assertJsonValidationErrors('images');
        $this->assertSame(0, PropertyImage::count());

        $this->upload(array_slice($files, 0, 10))->assertStatus(201);
    }

    public function test_images_field_is_required(): void
    {
        $this->actingAs($this->agentUser)
            ->postJson("/api/properties/{$this->property->id}/images", [])
            ->assertStatus(422)->assertJsonValidationErrors('images');
    }

    public function test_only_the_owning_agent_or_agency_admin_can_upload(): void
    {
        $colleague = $this->makeAgent($this->agency);
        $outsider = $this->makeAdmin();
        $client = $this->makeClientUser($this->agency);

        $this->upload([$this->jpg()], $colleague)->assertStatus(403);
        $this->upload([$this->jpg()], $client)->assertStatus(403);
        $this->upload([$this->jpg()], $outsider)->assertStatus(404);
        $this->upload([$this->jpg()], $this->makeAdmin($this->agency))->assertStatus(201);
    }
}
