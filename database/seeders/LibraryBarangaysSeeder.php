<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class LibraryBarangaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangays = [
            'Apokon',
            'Bincungan',
            'Busaon',
            'Canocotan',
            'Cuambogan',
            'La Filipina',
            'Liboganon',
            'Madaum',
            'Magdum',
            'Magugpo East',
            'Magugpo North',
            'Magugpo Poblacion',
            'Magugpo South',
            'Magugpo West',
            'Mankilam',
            'New Balamban',
            'Nueva Fuerza',
            'Pagsabangan',
            'Pandapan',
            'San Agustin',
            'San Isidro',
            'San Miguel',
            'Visayan Village',
        ];

        foreach ($barangays as $barangay) {
            Barangay::firstOrCreate(['name' => $barangay]);
        }
    }
}
