<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BettingType;

class BettingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bettingTypes = [
            [
                'name' => 'Bao Lô Đảo',
                'code' => 'bao_lo_dao',
                'syntaxes' => ['baod', 'baodao', 'baold', 'baoldao', 'baolod', 'baolodao', 'bd', 'bdao', 'bld', 'bldao', 'blod', 'blodao', 'ld', 'ldao', 'lod', 'lodao', 'lo dao', 'daolo', 'dao lo'],
                'description' => 'Bao lô đảo - cược tất cả các số đảo ngược',
                'sort_order' => 1
            ],
            [
                'name' => 'Bao Lô',
                'code' => 'bao_lo',
                'syntaxes' => ['b', 'bao', 'baol', 'baolo', 'bl', 'blo', 's', 'bao lo'],
                'description' => 'Bao lô - cược tất cả các số',
                'sort_order' => 2
            ],
            [
                'name' => 'Lô',
                'code' => 'lo',
                'syntaxes' => ['lo'],
                'description' => 'Lô - cược số đơn',
                'sort_order' => 3
            ],
            [
                'name' => 'Đá Thẳng',
                'code' => 'da_thang',
                'syntaxes' => ['da', 'dat', 'dathang', 'da thang', 'dadoc', 'da doc', 'dthang', 'dadao', 'da dao'],
                'description' => 'Đá thẳng - cược 2 số thẳng',
                'sort_order' => 4
            ],
            [
                'name' => 'Đầu',
                'code' => 'dau',
                'syntaxes' => ['d', 'dau'],
                'description' => 'Đầu - cược số đầu',
                'sort_order' => 5
            ],
            [
                'name' => 'Đá Xéo',
                'code' => 'da_xeo',
                'syntaxes' => ['dac', 'dax', 'dx', 'daxeo', 'dacheo', 'da xeo', 'da cheo', 'dxeo'],
                'description' => 'Đá xéo - cược 2 số chéo',
                'sort_order' => 6
            ],
            [
                'name' => 'Số Cặp',
                'code' => 'so_cap',
                'syntaxes' => ['socap'],
                'description' => 'Số cặp - 00 11 22 33 44 55 66 77 88 99',
                'sort_order' => 7
            ],
            [
                'name' => 'Đầu Đuôi',
                'code' => 'dau_duoi',
                'syntaxes' => ['dc', 'dd', 'd d', 'dauduoi', 'daudui', 'dau duoi', 'dau dui', 'daucuoi'],
                'description' => 'Đầu đuôi - cược đầu và đuôi',
                'sort_order' => 8
            ],
            [
                'name' => 'Đuôi',
                'code' => 'duoi',
                'syntaxes' => ['c', 'db', 'de', 'du', 'dui', 'duoi'],
                'description' => 'Đuôi - cược số đuôi',
                'sort_order' => 9
            ],
            [
                'name' => 'Đuôi Đảo',
                'code' => 'duoi_dao',
                'syntaxes' => ['cd', 'cdao', 'daoc', 'daodb', 'daode', 'daodu', 'daodui', 'daoduoi', 'dbd', 'dbdao', 'ddb', 'dde', 'ddu', 'ddui', 'dduoi', 'dec', 'ded', 'dedao', 'dud', 'dudao', 'duid', 'duidao', 'duoid', 'duoidao'],
                'description' => 'Đuôi đảo - cược đuôi đảo ngược',
                'sort_order' => 10
            ],
            [
                'name' => 'Xiên 3',
                'code' => 'xien_3',
                'syntaxes' => ['ba', 'xa', 'xba', 'x3', 'xien3', 'xn3', 'xi3'],
                'description' => 'Xiên 3 - cược 3 số xiên',
                'sort_order' => 11
            ],
            [
                'name' => 'Xiên 4',
                'code' => 'xien_4',
                'syntaxes' => ['xbo', 'xo', 'x4', 'xien4', 'xn4', 'xi4'],
                'description' => 'Xiên 4 - cược 4 số xiên',
                'sort_order' => 12
            ],
            [
                'name' => 'Kéo Hàng Ngàn',
                'code' => 'keo_hang_ngan',
                'syntaxes' => ['kng', 'keongan', 'keohangngan', 'kngan'],
                'description' => 'Kéo hàng ngàn',
                'sort_order' => 13
            ],
            [
                'name' => 'Kéo Hàng Trăm',
                'code' => 'keo_hang_tram',
                'syntaxes' => ['ktr', 'keotram', 'keohangtram', 'ktram'],
                'description' => 'Kéo hàng trăm',
                'sort_order' => 14
            ],
            [
                'name' => 'Kéo Hàng Chục',
                'code' => 'keo_hang_chuc',
                'syntaxes' => ['kch', 'keochuc', 'keohangchuc', 'kchuc'],
                'description' => 'Kéo hàng chục',
                'sort_order' => 15
            ],
            [
                'name' => 'Xỉu Chủ',
                'code' => 'xiu_chu',
                'syntaxes' => ['Sc', 'xc', 'xchu', 'xiuchu'],
                'description' => 'Xỉu chủ',
                'sort_order' => 16
            ],
            [
                'name' => 'Xỉu Chủ Cặp',
                'code' => 'xiu_chu_cap',
                'syntaxes' => ['xccap', 'xiuchucap', 'xchucap'],
                'description' => 'Xỉu chủ cặp - 000 111 222 333 444 555 666 777 888 999',
                'sort_order' => 17
            ],
            [
                'name' => 'Xỉu Chủ Đảo',
                'code' => 'xiu_chu_dao',
                'syntaxes' => ['daodd', 'daoxc', 'ddd', 'dddao', 'dxc', 'xcd', 'xcdao', 'xiuchudao', 'xchudao'],
                'description' => 'Xỉu chủ đảo',
                'sort_order' => 18
            ],
            [
                'name' => 'Xỉu Chủ Đầu',
                'code' => 'xiu_chu_dau',
                'syntaxes' => ['xcdau', 'xiuchudau', 'xchudau'],
                'description' => 'Xỉu chủ đầu',
                'sort_order' => 19
            ],
            [
                'name' => 'Xỉu Chủ Đầu Đảo',
                'code' => 'xiu_chu_dau_dao',
                'syntaxes' => ['xcdaodau', 'xcdaud', 'xcdaudao', 'xcddau', 'xiuchudaudao', 'xchudaudao', 'daudao', 'xcdadao', 'daodau', 'xchudaodau', 'xiuchudaodau'],
                'description' => 'Xỉu chủ đầu đảo',
                'sort_order' => 20
            ],
            [
                'name' => 'Xỉu Chủ Đuôi',
                'code' => 'xiu_chu_duoi',
                'syntaxes' => ['xcc', 'xcdb', 'xcde', 'xcdu', 'xcdui', 'xcduoi', 'xiuchudui', 'xchuduoi', 'xchudui', 'xiuchuduoi'],
                'description' => 'Xỉu chủ đuôi',
                'sort_order' => 21
            ],
            [
                'name' => 'Xỉu Chủ Đuôi Đảo',
                'code' => 'xiu_chu_duoi_dao',
                'syntaxes' => ['xcdaoc', 'xcdaodb', 'xcdaode', 'xcdaodu', 'xcdaodui', 'xcdaoduoi', 'xcdbdao', 'xcdc', 'xcdde', 'xcddu', 'xcddui', 'xcdduoi', 'xcded', 'xcdedao', 'xcdud', 'xcdudao', 'xcduid', 'xcduidao', 'xcduoid', 'xcduoidao', 'xiuchuduoidao', 'xiuchuduidao', 'xiuchudaoduoi', 'xiuchudaodui', 'xchuduoidao', 'xchuduidao', 'xchudaoduoi'],
                'description' => 'Xỉu chủ đuôi đảo',
                'sort_order' => 22
            ],
            [
                'name' => 'Xiên Đảo',
                'code' => 'xien_dao',
                'syntaxes' => ['daox', 'daoxi', 'daoxien', 'daoxin', 'dxi', 'dxien', 'dxin', 'xd', 'xdao', 'xid', 'xidao', 'xiend', 'xiendao', 'xind', 'xindao', 'xndao'],
                'description' => 'Xiên đảo',
                'sort_order' => 23
            ],
            [
                'name' => 'Xiên 2',
                'code' => 'xien_2',
                'syntaxes' => ['x', 'xi', 'xien', 'xin', 'xien2', 'xn'],
                'description' => 'Xiên 2 - cược 2 số xiên',
                'sort_order' => 24
            ],
            [
                'name' => 'Nhỏ Chẵn',
                'code' => 'nho_chan',
                'syntaxes' => ['nhochan'],
                'description' => 'Nhỏ chẵn - 00 02 04 06 08 10 12 14 16 18 20 22 24 26 28 30 32 34 36 38 40 42 44 46 48',
                'sort_order' => 25
            ],
            [
                'name' => 'Nhỏ Lẻ',
                'code' => 'nho_le',
                'syntaxes' => ['nhole'],
                'description' => 'Nhỏ lẻ - 01 03 05 07 09 11 13 15 17 19 21 23 25 27 29 31 33 35 37 39 41 43 45 47 49',
                'sort_order' => 26
            ],
            [
                'name' => 'Lớn Chẵn',
                'code' => 'lon_chan',
                'syntaxes' => ['lonchan'],
                'description' => 'Lớn chẵn - 50 52 54 56 58 60 62 64 66 68 70 72 74 76 78 80 82 84 86 88 90 92 94 96 98',
                'sort_order' => 27
            ],
            [
                'name' => 'Lớn Lẻ',
                'code' => 'lon_le',
                'syntaxes' => ['lonle'],
                'description' => 'Lớn lẻ - 51 53 55 57 59 61 63 65 67 69 71 73 75 77 79 81 83 85 87 89 91 93 95 97 99',
                'sort_order' => 28
            ],
            [
                'name' => 'Chục Chẵn Tổng Lớn',
                'code' => 'chuc_chan_tong_lon',
                'syntaxes' => ['chucchantonglon'],
                'description' => 'Chục chẵn tổng lớn - 05 06 07 08 09 23 24 25 26 27 41 42 43 44 45 60 61 62 63 69 80 81 87 88 89',
                'sort_order' => 29
            ],
            [
                'name' => 'Chục Chẵn Tổng Nhỏ',
                'code' => 'chuc_chan_tong_nho',
                'syntaxes' => ['chucchantongnho'],
                'description' => 'Chục chẵn tổng nhỏ - 00 01 02 03 04 20 21 22 28 29 40 46 47 48 49 64 65 66 67 68 82 83 84 85 86',
                'sort_order' => 30
            ],
            [
                'name' => 'Chục Lẻ Tổng Lớn',
                'code' => 'chuc_le_tong_lon',
                'syntaxes' => ['chucletonglon'],
                'description' => 'Chục lẻ tổng lớn - 14 15 16 17 18 32 33 34 35 36 50 51 52 53 54 70 71 72 78 79 90 96 97 98 99',
                'sort_order' => 31
            ],
            [
                'name' => 'Chục Lẻ Tổng Nhỏ',
                'code' => 'chuc_le_tong_nho',
                'syntaxes' => ['chucletongnho'],
                'description' => 'Chục lẻ tổng nhỏ - 10 11 12 13 19 30 31 37 38 39 55 56 57 58 59 73 74 75 76 77 91 92 93 94 95',
                'sort_order' => 32
            ],
            [
                'name' => 'Chẵn Lớn',
                'code' => 'chan_lon',
                'syntaxes' => ['chanlon'],
                'description' => 'Chẵn lớn - 05 06 07 08 09 25 26 27 28 29 45 46 47 48 49 65 66 67 68 69 85 86 87 88 89',
                'sort_order' => 33
            ],
            [
                'name' => 'Chẵn Nhỏ',
                'code' => 'chan_nho',
                'syntaxes' => ['channho'],
                'description' => 'Chẵn nhỏ - 00 01 02 03 04 20 21 22 23 24 40 41 42 43 44 60 61 62 63 64 80 81 82 83 84',
                'sort_order' => 34
            ],
            [
                'name' => 'Lẻ Lớn',
                'code' => 'le_lon',
                'syntaxes' => ['lelon'],
                'description' => 'Lẻ lớn - 15 16 17 18 19 35 36 37 38 39 55 56 57 58 59 75 76 77 78 79 95 96 97 98 99',
                'sort_order' => 35
            ],
            [
                'name' => 'Lẻ Nhỏ',
                'code' => 'le_nho',
                'syntaxes' => ['lenho'],
                'description' => 'Lẻ nhỏ - 10 11 12 13 14 30 31 32 33 34 50 51 52 53 54 70 71 72 73 74 90 91 92 93 94',
                'sort_order' => 36
            ],
            [
                'name' => 'Lớn Lớn',
                'code' => 'lon_lon',
                'syntaxes' => ['lonlon'],
                'description' => 'Lớn lớn - 55 56 57 58 59 65 66 67 68 69 75 76 77 78 79 85 86 87 88 89 95 96 97 98 99',
                'sort_order' => 37
            ],
            [
                'name' => 'Lớn Nhỏ',
                'code' => 'lon_nho',
                'syntaxes' => ['lonnho'],
                'description' => 'Lớn nhỏ - 50 51 52 53 54 60 61 62 63 64 70 71 72 73 74 80 81 82 83 84 90 91 92 93 94',
                'sort_order' => 38
            ],
            [
                'name' => 'Nhỏ Lớn',
                'code' => 'nho_lon',
                'syntaxes' => ['nholon'],
                'description' => 'Nhỏ lớn - 05 06 07 08 09 15 16 17 18 19 25 26 27 28 29 35 36 37 38 39 45 46 47 48 49',
                'sort_order' => 39
            ],
            [
                'name' => 'Nhỏ Nhỏ',
                'code' => 'nho_nho',
                'syntaxes' => ['nhonho'],
                'description' => 'Nhỏ nhỏ - 00 01 02 03 04 10 11 12 13 14 20 21 22 23 24 30 31 32 33 34 40 41 42 43 44',
                'sort_order' => 40
            ],
            [
                'name' => 'Chục Lớn Tổng Lớn',
                'code' => 'chuc_lon_tong_lon',
                'syntaxes' => ['chuclontonglon'],
                'description' => 'Chục lớn tổng lớn - 50 51 52 53 54 60 61 62 63 69 70 71 72 78 79 80 81 87 88 89 90 96 97 98 99',
                'sort_order' => 41
            ],
            [
                'name' => 'Chục Lớn Tổng Nhỏ',
                'code' => 'chuc_lon_tong_nho',
                'syntaxes' => ['chuclontongnho'],
                'description' => 'Chục lớn tổng nhỏ - 55 56 57 58 59 64 65 66 67 68 73 74 75 76 77 82 83 84 85 86 91 92 93 94 95',
                'sort_order' => 42
            ],
            [
                'name' => 'Chục Nhỏ Tổng Lớn',
                'code' => 'chuc_nho_tong_lon',
                'syntaxes' => ['chucnhotonglon'],
                'description' => 'Chục nhỏ tổng lớn - 05 06 07 08 09 14 15 16 17 18 23 24 25 26 27 32 33 34 35 36 41 42 43 44 45',
                'sort_order' => 43
            ],
            [
                'name' => 'Chục Nhỏ Tổng Nhỏ',
                'code' => 'chuc_nho_tong_nho',
                'syntaxes' => ['chucnhotongnho'],
                'description' => 'Chục nhỏ tổng nhỏ - 00 01 02 03 04 10 11 12 13 19 20 21 22 28 29 30 31 37 38 39 40 46 47 48 49',
                'sort_order' => 44
            ],
            [
                'name' => 'Giáp',
                'code' => 'giap',
                'syntaxes' => ['giap', 'congiap'],
                'description' => 'Giáp - 06 07 09 10 11 12 14 15 18 23 26 28 32 35 46 47 49 50 51 52 54 55 58 63 66 68 72 75 86 87 89 90 91 92 94 95 98',
                'sort_order' => 45
            ],
            [
                'name' => 'Không Giáp',
                'code' => 'khong_giap',
                'syntaxes' => ['kogiap', 'khonggiap', 'khongcongiap', 'kgiap'],
                'description' => 'Không giáp - tất cả số còn lại',
                'sort_order' => 46
            ],
            [
                'name' => 'Chẵn Lẻ',
                'code' => 'chan_le',
                'syntaxes' => ['chanle'],
                'description' => 'Chẵn lẻ - 01 03 05 07 09 21 23 25 27 29 41 43 45 47 49 61 63 65 67 69 81 83 85 87 89',
                'sort_order' => 47
            ],
            [
                'name' => 'Lẻ Chẵn',
                'code' => 'le_chan',
                'syntaxes' => ['lechan'],
                'description' => 'Lẻ chẵn - 10 12 14 16 18 30 32 34 36 38 50 52 54 56 58 70 72 74 76 78 90 92 94 96 98',
                'sort_order' => 48
            ],
            [
                'name' => 'Chẵn Chẵn',
                'code' => 'chan_chan',
                'syntaxes' => ['chanchan'],
                'description' => 'Chẵn chẵn - 00 02 04 06 08 20 22 24 26 28 40 42 44 46 48 60 62 64 66 68 80 82 84 86 88',
                'sort_order' => 49
            ],
            [
                'name' => 'Lẻ Lẻ',
                'code' => 'le_le',
                'syntaxes' => ['lele'],
                'description' => 'Lẻ lẻ - 11 13 15 17 19 31 33 35 37 39 51 53 55 57 59 71 73 75 77 79 91 93 95 97 99',
                'sort_order' => 50
            ],
            [
                'name' => 'Chục Chẵn',
                'code' => 'chuc_chan',
                'syntaxes' => ['chucchan'],
                'description' => 'Chục chẵn - 00 01 02 03 04 05 06 07 08 09 20 21 22 23 24 25 26 27 28 29 40 41 42 43 44 45 46 47 48 49 60 61 62 63 64 65 66 67 68 69 80 81 82 83 84 85 86 87 88 89',
                'sort_order' => 51
            ],
            [
                'name' => 'Chục Lẻ',
                'code' => 'chuc_le',
                'syntaxes' => ['chucle'],
                'description' => 'Chục lẻ - 10 11 12 13 14 15 16 17 18 19 30 31 32 33 34 35 36 37 38 39 50 51 52 53 54 55 56 57 58 59 70 71 72 73 74 75 76 77 78 79 90 91 92 93 94 95 96 97 98 99',
                'sort_order' => 52
            ],
            [
                'name' => 'Đơn Vị Chẵn',
                'code' => 'don_vi_chan',
                'syntaxes' => ['dvichan', 'donvichan', 'ditchan'],
                'description' => 'Đơn vị chẵn - 00 02 04 06 08 10 12 14 16 18 20 22 24 26 28 30 32 34 36 38 40 42 44 46 48 50 52 54 56 58 60 62 64 66 68 70 72 74 76 78 80 82 84 86 88 90 92 94 96 98',
                'sort_order' => 53
            ],
            [
                'name' => 'Đơn Vị Lẻ',
                'code' => 'don_vi_le',
                'syntaxes' => ['dvile', 'donvile', 'ditle'],
                'description' => 'Đơn vị lẻ - 01 03 05 07 09 11 13 15 17 19 21 23 25 27 29 31 33 35 37 39 41 43 45 47 49 51 53 55 57 59 61 63 65 67 69 71 73 75 77 79 81 83 85 87 89 91 93 95 97 99',
                'sort_order' => 54
            ],
            [
                'name' => 'Đơn Vị Xỉu',
                'code' => 'don_vi_xiu',
                'syntaxes' => ['dvixiu', 'donvixiu'],
                'description' => 'Đơn vị xỉu - 00 01 02 03 04 10 11 12 13 14 20 21 22 23 24 30 31 32 33 34 40 41 42 43 44 50 51 52 53 54 60 61 62 63 64 70 71 72 73 74 80 81 82 83 84 90 91 92 93 94',
                'sort_order' => 55
            ],
            [
                'name' => 'Đơn Vị Tài',
                'code' => 'don_vi_tai',
                'syntaxes' => ['dvitai', 'donvitai'],
                'description' => 'Đơn vị tài - 05 06 07 08 09 15 16 17 18 19 25 26 27 28 29 35 36 37 38 39 45 46 47 48 49 55 56 57 58 59 65 66 67 68 69 75 76 77 78 79 85 86 87 88 89 95 96 97 98 99',
                'sort_order' => 56
            ],
            [
                'name' => 'Tổng 0',
                'code' => 'tong_0',
                'syntaxes' => ['tong0'],
                'description' => 'Tổng 0 - 00 19 91 28 82 37 73 46 64 55',
                'sort_order' => 57
            ],
            [
                'name' => 'Tổng 1',
                'code' => 'tong_1',
                'syntaxes' => ['tong1'],
                'description' => 'Tổng 1 - 01 10 29 92 38 83 47 74 56 65',
                'sort_order' => 58
            ],
            [
                'name' => 'Tổng 2',
                'code' => 'tong_2',
                'syntaxes' => ['tong2'],
                'description' => 'Tổng 2 - 02 20 39 93 48 84 57 75 66 11',
                'sort_order' => 59
            ],
            [
                'name' => 'Tổng 3',
                'code' => 'tong_3',
                'syntaxes' => ['tong3'],
                'description' => 'Tổng 3 - 03 30 49 94 12 21 76 67 85 58',
                'sort_order' => 60
            ],
            [
                'name' => 'Tổng 4',
                'code' => 'tong_4',
                'syntaxes' => ['tong4'],
                'description' => 'Tổng 4 - 04 40 95 59 68 86 77 22 13 31',
                'sort_order' => 61
            ],
            [
                'name' => 'Tổng 5',
                'code' => 'tong_5',
                'syntaxes' => ['tong5'],
                'description' => 'Tổng 5 - 05 50 14 41 23 32 78 87 96 69',
                'sort_order' => 62
            ],
            [
                'name' => 'Tổng 6',
                'code' => 'tong_6',
                'syntaxes' => ['tong6'],
                'description' => 'Tổng 6 - 15 51 24 42 33 60 06 79 97 88',
                'sort_order' => 63
            ],
            [
                'name' => 'Tổng 7',
                'code' => 'tong_7',
                'syntaxes' => ['tong7'],
                'description' => 'Tổng 7 - 07 70 16 61 25 52 34 43 89 98',
                'sort_order' => 64
            ],
            [
                'name' => 'Tổng 8',
                'code' => 'tong_8',
                'syntaxes' => ['tong8'],
                'description' => 'Tổng 8 - 08 80 17 71 26 62 35 53 44 99',
                'sort_order' => 65
            ],
            [
                'name' => 'Tổng 9',
                'code' => 'tong_9',
                'syntaxes' => ['tong9'],
                'description' => 'Tổng 9 - 09 90 18 81 27 72 36 63 45 54',
                'sort_order' => 66
            ],
            [
                'name' => 'Xỉu',
                'code' => 'xiu',
                'syntaxes' => ['xiu'],
                'description' => 'Xỉu - 00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49',
                'sort_order' => 67
            ],
            [
                'name' => 'Tài',
                'code' => 'tai',
                'syntaxes' => ['tai'],
                'description' => 'Tài - 50 51 52 53 54 55 56 57 58 59 60 61 62 63 64 65 66 67 68 69 70 71 72 73 74 75 76 77 78 79 80 81 82 83 84 85 86 87 88 89 90 91 92 93 94 95 96 97 98 99',
                'sort_order' => 68
            ],
            [
                'name' => 'Tổng Chẵn',
                'code' => 'tong_chan',
                'syntaxes' => ['tongchan'],
                'description' => 'Tổng chẵn - 00 02 04 06 08 11 13 15 17 19 20 22 24 26 28 31 33 35 37 39 40 42 44 46 48 51 53 55 57 59 60 62 64 66 68 71 73 75 77 79 80 82 84 86 88 91 93 95 97 99',
                'sort_order' => 69
            ],
            [
                'name' => 'Tổng Lẻ',
                'code' => 'tong_le',
                'syntaxes' => ['tongle'],
                'description' => 'Tổng lẻ - 01 03 05 07 09 10 12 14 16 18 21 23 25 27 29 30 32 34 36 38 41 43 45 47 49 50 52 54 56 58 61 63 65 67 69 70 72 74 76 78 81 83 85 87 89 90 92 94 96 98',
                'sort_order' => 70
            ],
            [
                'name' => 'Số Cặp',
                'code' => 'so_cap_2',
                'syntaxes' => ['socap', 'scap'],
                'description' => 'Số cặp - 00 11 22 33 44 55 66 77 88 99',
                'sort_order' => 71
            ],
            [
                'name' => 'Kéo Hàng Đơn Vị',
                'code' => 'keo_hang_don_vi',
                'syntaxes' => ['keo', 'kdv', 'keodonvi', 'keohangdonvi', 'kdonvi', 'kdvi', 'den', 'k'],
                'description' => 'Kéo hàng đơn vị',
                'sort_order' => 72
            ],
            [
                'name' => 'Hàng Chục',
                'code' => 'hang_chuc',
                'syntaxes' => ['hang', 'chuc'],
                'description' => 'Hàng chục',
                'sort_order' => 73
            ],
            [
                'name' => 'Đít Đơn Vị',
                'code' => 'dit_don_vi',
                'syntaxes' => ['dit', 'dvi', 'donvi'],
                'description' => 'Đít đơn vị',
                'sort_order' => 74
            ],
            [
                'name' => 'Kế Đầu',
                'code' => 'ke_dau',
                'syntaxes' => ['kdau', 'ka', 'kea', 'kedau'],
                'description' => 'Kế đầu',
                'sort_order' => 75
            ],
            [
                'name' => 'Kế Đuôi',
                'code' => 'ke_duoi',
                'syntaxes' => ['kdui', 'kb', 'keb', 'kduoi', 'keduoi', 'kedui'],
                'description' => 'Kế đuôi',
                'sort_order' => 76
            ],
            [
                'name' => 'Kế Đầu Đuôi',
                'code' => 'ke_dau_duoi',
                'syntaxes' => ['kedauduoi', 'kedaudui', 'kdauduoi', 'kdaudui', 'kddui', 'kab', 'keab'],
                'description' => 'Kế đầu đuôi',
                'sort_order' => 77
            ]
        ];

        foreach ($bettingTypes as $type) {
            BettingType::create($type);
        }
    }
}
