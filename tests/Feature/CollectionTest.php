<?php

namespace Tests\Feature;

use App\Data\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\LazyCollection;
use PDO;
use PHPUnit\Event\TestSuite\TestSuiteForTestMethodWithDataProvider;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertEqualsCanonicalizing;
use function PHPUnit\Framework\assertEqualsWithDelta;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class CollectionTest extends TestCase
{
    function testCreateCollection() {
        $collection = collect([1,2,3]);
        assertEquals([1,2,3], $collection->all());  //mengecek array hingga index nya
        assertEqualsCanonicalizing([1,2,3], $collection->all()); // mengecek array tanpa cek index
    }

    function testForeach() {
        // $arr = array(1,2,3,4,5,6,7,8,9);
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        foreach ($coll as $key => $value) {
            assertEquals($key + 1, $value);
        }
    }

    function testCrud() {
        $coll = collect([]);
        $coll->push(1,2,3);
        assertEqualsCanonicalizing([1,2,3], $coll->all());

        $result = $coll->pop();
        assertEquals(3,$result);
        assertEqualsCanonicalizing([1,2], $coll->all());
    }

    function testMap() {
        $coll = collect([1,2,3]);
        $result = $coll->map(function ($item) { //map harus disimpan ke variabel baru
            return $item * 2;
        });

        assertEquals([2,4,6], $result->all());
    }

    function testMapInto() {
        $coll = collect(["Rena"]);
        // melempar value kedalam instance seebuah class
        $result = $coll->mapInto(Person::class);
        assertEquals([new Person("Rena")], $result->all());
    }

    function testMapSpread() {
        // melakukan mapping dari data didalam seetiap array
        $coll = collect([["Kezia", "Putri"],["Maria","Regina"],["Rena","Putri"]]);
        $result = $coll->mapSpread(function ($firstName , $lastName)  {
            $fullName = $firstName . " " . $lastName;
            return new Person($fullName);
        });
        assertEquals([
            new Person("Kezia Putri"),
            new Person("Maria Regina"),
            new Person("Rena Putri"),
        ], $result->all());
    }

    function testMapToGroups() {
        // memisahkan berdasarkan value yang sama
        $coll = collect([
            [
                "name" => "Rena",
                "department" => "IT"
            ],
            [
                "name" => "Putri",
                "department" => "IT"
            ],
            [
                "name" => "Maria",
                "department" => "CEO"
            ],
        ]);

        $result = $coll->mapToGroups(function ($item) {
            return [$item['department'] => $item["name"]];
        });

        assertEquals([
            "IT" => collect(["Rena","Putri"]),
            "CEO" => collect(["Maria"]),
        ],$result->all());
    }

    function testZip() {
        $coll1 = collect([1,2,3]);
        $coll2 = collect([4,5,6]);
        $coll3 = $coll1->zip($coll2);

        // menggambungkan kedua item dari 2 collection kedalam collection baru
        assertEquals([
            collect([1,4]),
            collect([2,5]),
            collect([3,6]),
        ],$coll3->all());
    }

    function testConcat()  {
        $coll1 = collect([1,2,3]);
        $coll2 = collect([4,5,6]);
        $coll3 = $coll1->concat($coll2);
        // dd($coll3);
        // menggabungkan coll2 demgan item coll 1 tapi bukan keedalam collection baru (1,2,3,4,5,6)
        assertEquals(
            [1,2,3,4,5,6],
            $coll3->all()
        );
    }

    function testCombine()  {
        $coll1 = collect(["name", "occupation"]);
        $coll2 = collect(["Kezia Regina", "Backend Developer"]);
        $coll3 = $coll1->combine($coll2);
        // dd($coll3);
        // menggabungkan coll1 dan 2 menjadi key dan value pair name => kezia regina, occpation => beken dev
        assertEquals(
            [
                "name" => "Kezia Regina",
                "occupation" => "Backend Developer",
            ],
            $coll3->all()
        );
    }

    function testCollapse() {
        $coll = collect([
            [1,2,3],
            [4,5,6],
            [7,8,9],
        ]);

        $result = $coll->collapse();

        // memecah sebuah collection berisi array menjadi 1 array saja 
        // dd($result->all());
        assertEquals([1,2,3,4,5,6,7,8,9],$result->all());
    }
    
    function testFlatMap() {
        $coll = collect([
            [
                "name" => "Rena Putri",
                "job" => ["Software Engineer", "Backend Developer"]
            ],
            [
                "name" => "Maria Regina",
                "job" => ["Software Engineer", "DevOps Engineer"]
            ],
        ]);   

        /* menggunakan callback dan menerima parameter , 
        yang akan di flat adalah yang kita akses, disini "job", 
        tapi tidak menghiraukan nilai sama, artinya jika ada 2 data kembar ,
        maka 2 2 nya akan dipakai dan berurutan sesuai di array
         */
        $result = $coll->flatMap(function ($item) {
            return $item['job'];
        });

        // dd($result->all());
        assertEquals(["Software Engineer", "Backend Developer", "Software Engineer", "DevOps Engineer"], $result->all());
    }

    function testJoin() {
        $coll = collect(["Maria","Rena", "Poetri"]);

        // value langsung berubah , tapi jika ingin menyimpan value maka harus dimasukan ke variabel baru
        assertEquals("Maria-Rena-Poetri", $coll->join('-',''));
        assertEquals("Maria-Rena_Poetri", $coll->join('-','_'));
    }

    function testFiltering() {
        $coll = collect([
            "Maria" => 100,
            "Rena" => 62,
            "Poetri" => 92,
        ]);

        $result = $coll->filter(function ($item , $key) {
            return $item >= 90;
            // return $key == 'Maria';
        });

        // dd($result->all());
        // jadi ini berfungsi untuk filter data berdasar item(value) atau key
        assertEquals([
            "Maria" => 100,
            "Poetri" => 92
        ], $result->all());
    }

    function testFilterIndex() {
        $coll = collect([1,2,3,4,5,6,7,8,9,10]);
        $result = $coll->filter(function ($item){
            return $item % 2 == 0;
        });

        /* tidak direkomendasikan menggunakan filter di data array index,
        karena value yang tiidak sesuai dgn kondisi akan dibuang, dan index tidak bergeser */
        assertEqualsCanonicalizing([2,4,6,8,10], $result->all()); //ini main aman
        // assertEquals([2,4,6,8,10], $result->all()); //ini akan error karena data yang didalem $result itu index nya tidak bergeser
    }

    function testPartition() {
        $coll = collect([
            "Maria" => 100,
            "Rena" => 62,
            "Poetri" => 92,
        ]);

        // data yang sesuai kondisi akan masuk array pertama , sedangkan yang tidak sesuai kondisi akan masuk array ke 2
        [$res1,$res2] = $coll->partition(function ($item,$key) {
            return $item >= 90;
        });
        
        assertEquals([
            "Maria" => 100,
            "Poetri" => 92
        ],$res1->all());
        
        assertEquals([
            "Rena" => 62,
        ],$res2->all());
    }

    function testTesting () {
        $coll = collect(["Maria","Rena", "Poetri"]);
        // $coll = collect([
        //     "Maria" => 100,
        //     "Rena" => 62,
        //     "Poetri" => 92,
        // ]);

        // $res = $coll->contains(function ($item){
        //     return $item >= 90;
        // });

        // $res = $coll->contains(function ($item){
        //     return $item == 'Maria'; //jangan masukan variabel karena ini hasilnya boolean, bukan mengembalikan data
        // });

        // assertTrue($coll->has("Maria"));
        // assertTrue($coll->hasAny(["Maria","Eko"]));
        // assertTrue($coll->contains(100));
        // dd($res);

        // ini semua hanya akan mengembalikan boolean
        assertTrue($coll->contains("Maria"));
        assertTrue($coll->contains(function ($item) {
            return $item == 'Rena';
        }));
        // assertTrue("Maria",$res); 
        // assertTrue($coll->contains("Maria",100));
    }

    function testGrouping() {
        $coll = collect([
            [
                "name" => "Rena",
                "department" => "IT"
            ],
            [
                "name" => "Putri",
                "department" => "IT"
            ],
            [
                "name" => "Maria",
                "department" => "CEO"
            ],
        ]);

        /* hampir mirip dengan mapToGroup ,
        bedanya maptogroup hanya mengembalikan value dari data yang sesuai, 
        "IT" => collect(["Rena","POetri"]) , 
        sedangkan ini adalah seluruh data yang dikembalikan : 
        "IT" => collect([
                [
                    "name" => "Rena",
                    "department" => "IT"
                ],
                [
                    "name" => "Putri",
                    "department" => "IT"
                ],
            ]),
        */
        $res = $coll->groupBy('department');
        $res2 = $coll->groupBy(function ($key) {
            return $key['department'];
        });

        assertEquals([
            "IT" => collect([
                [
                    "name" => "Rena",
                    "department" => "IT"
                ],
                [
                    "name" => "Putri",
                    "department" => "IT"
                ],
            ]),
            "CEO" => collect([
                [
                    "name" => "Maria",
                    "department" => "CEO"
                ],
            ]),
        ],$res->all());
        // dd($res2);
    }

    function testSlice() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->slice(3); //tetap menggunakan index, index ke 3 adalah 4
        $res2 = $coll->slice(3,2); //setelah index ke 3, ambil data sebanyak 2 kali

        /* dan data yang di pisah (slice) index nya akan tetap sama (tidak dimulai lagi dari 0)
        contoh:
        3 => 4
        4 => 5
        5 => 6
        6 => 7
        7 => 8
        8 => 9
        */
        assertEqualsCanonicalizing([4,5,6,7,8,9],$res->all());
        assertEqualsCanonicalizing([4,5],$res2->all());
    }

    function testTake() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->take(3); //berbeda dari slice, take tidak pakai index melainkan memakai length dari array
        assertEquals([1,2,3],$res->all());
        
        /*
        take berbeda dengan filter karena take akan berhenti eksekusi ketika kondisi sudah true/false,
        maka nilai lain setelah nya tidak akan di saring lagi,
        */
        $res2 = $coll->takeUntil(function($value) {
            return $value == 5; //mengambil nilai hingga kondisi true, kondisi true tidak akan diambil
        });
        assertEquals([1,2,3,4],$res2->all());

        $res3 = $coll->takeWhile(function($value) {
            return $value <= 5; //meengambil nilai selama kondisi true
        });
        assertEquals([1,2,3,4,5],$res3->all());
    }

    function testSkip() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->skip(5); //dia skip data sepanjang length yang diberikan, tapi index key akan tetap danm tidak bergeser
        assertEqualsCanonicalizing([6,7,8,9],$res->all()); 
        
        $res2 = $coll->skipUntil(function ($value) {
            return $value == 5; //jika sudah true maka data akan diambil, data true pertama (5) juga diambil
        });
        assertEqualsCanonicalizing([5,6,7,8,9],$res2->all()); 
        
        $res3 = $coll->skipWhile(function ($value) {
            return $value <= 5; //jika sudah true maka data akan diambil, data true pertama (5) juga diambil
        });
        assertEqualsCanonicalizing([6,7,8,9],$res3->all()); 
    }

    function testChunked() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        /*membagi collection dengan angka 4 , 
        dan di setiap collection baru index nya menggunakan index sebelum di chunk,
        tidak mulai lagi dari 0 */
        $res = $coll->chunk(4); 
        // dd($res->all()[0]->all());
        assertEquals([1,2,3,4],$res->all()[0]->all());
        assertEqualsCanonicalizing([5,6,7,8],$res->all()[1]->all());
        assertEqualsCanonicalizing([9],$res->all()[2]->all());
    }

    function testFirst() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->first(); //mengambil value pertama tanpa ada kondisi, jika tidak ada data maka akan mengembalikan null
        // dd($res);
        assertEquals(1,$res);

        $res2 = $coll->first(function ($item) {
            return $item % 2 == 0 ; //mengambil value pertama yang sesuai dengan kondisi
        });
        assertEquals(2,$res2);
    }

    function testLast() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->last(); //mengambil value terakhir tanpa ada kondisi, jika tidak ada data maka akan mengembalikan null
        // dd($res);
        assertEquals(9,$res);

        $res2 = $coll->last(function ($item) {
            return $item % 2 == 0 ; //mengambil value terakhir yang sesuai dengan kondisi
        });
        assertEquals(8,$res2);
    }

    function testRandom() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->random(); //mengambil hanya 1 data random dr collection
        assertTrue(in_array($res,[1,2,3,4,5,6,7,8,9]));
        // dd($res);
        // $res2 = $coll->random(5); //mengembalikan value random ke dalam bentuk colletion 
        // dd($res2);
        // assertTrue($res2->hasAny($coll));
    }

    function testCheckingExistence() {
        // buat ngecek ada atau tidak nya sebuah data
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        assertTrue($coll->isNotEmpty());
        assertFalse($coll->isEmpty());
        assertTrue($coll->contains(1));
        assertFalse($coll->contains(100));
        assertTrue($coll->contains(function ($item) {
            return $item % 2 == 0;
        }));
    }

    function testOrdering() {
        $coll = collect([1,3,2,4,5,9,6,8,7]);
        //walau pun mengurutkan, index tidak berubah dan index akan ikut teracak sesuai posisi item
        $res = $coll->sort();
        // dd($res->all()); 
        assertEqualsCanonicalizing([1,2,3,4,5,6,7,8,9],$res->all());
        
        $res2 = $coll->sortDesc();
        assertEqualsCanonicalizing([9,8,7,6,5,4,3,2,1],$res2->all());
    }

    function testAggregat() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $min = $coll->min();
        assertEquals(1,$min);
        
        $max = $coll->max();
        assertEquals(9,$max);
        
        // dd($avg);
        $avg = $coll->average();
        assertEquals(5,$avg);
        
        $sum = $coll->sum(); //menjumlahkah semua value
        assertEquals(45,$sum);
        
        $count = $coll->count(); //menghitung banyaknya value
        assertEquals(9,$count);
    }

    function testReduce() {
        $coll = collect([1,2,3,4,5,6,7,8,9]);
        $res = $coll->reduce(function ($carry, $item) {
            return $carry + $item;
        });
        // entah gunanya secara sepesifik apa , tapi bisa buat custom operasi matematika?
        assertEquals(45,$res);
    }

    function testLazyCollection() {
        /* The above code is creating a lazy collection in PHP using a generator function. The
        `LazyCollection::make()` method is used to create a lazy collection that generates an infinite
        sequence of numbers starting from 0. The `yield` keyword is used to yield each value in the
        sequence, and the value is incremented in each iteration of the generator function. This
        allows for efficient iteration over the collection without generating all values upfront. */
        $coll = LazyCollection::make(function () {
            $value = 0;
            while (true) {
                yield $value;
                $value++;
            }
        });

        $res = $coll->take(10);
        dd($res);
        assertEqualsCanonicalizing([0,1,2,3,4,5,6,7,8,9],$res->all());
    }
}

