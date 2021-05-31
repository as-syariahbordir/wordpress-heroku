<?php
$imgbase = plugin_dir_url( dirname(__FILE__) ) ."images/";
?>

<!-- Navigation
    ================================================== -->
<div class="hero-background">
    <div>
        <img class="strips" src="<?php echo $imgbase; ?>strips.png">
    </div>
    <div class="container" style="width: 100%;">
        <div class="header-container header">
            <?php
            if( !$this->lib->is_license_active() ) {
                ?>
                <a href="<?php echo $this->upgrade_pro_url; ?>">
                    <button class="header-btn"> Upgrade ke Versi PRO!</button>
                </a>
                <?php
            }
            ?>
            <div class="header-right">
                <a class="navbar-item" href="<?php echo $this->docs_url; ?>">Dokumentasi</a>
                <a class="navbar-item" href="<?php echo $this->contact_url; ?>">Kontak Kami</a>
            </div>
        </div>
        <!--navigation-->


        <!-- Hero-Section
          ================================================== -->

        <div class="hero row">
            <div class="hero-right col-sm-6 col-sm-6">
                <h1 class="header-headline bold">Hebat!<br>Kamu telah menggunakan plugin ongkos kirim terbaik di Indonesia!<br></h1>
                <h4 class="header-running-text light"> OngkosKirim.id merupakan plugin ongkos kirim woocommerce dengan fitur terkomplit dan ekspedisi terlengkap, meliputi JNE, TIKI, POS, J&T, Sicepat, Wahana</h4>
                <a href="<?php echo $this->settings_url; ?>">
                    <button class="hero-btn"> Konfigurasi!</button>
                </a>
                <a href="<?php echo $this->docs_url; ?>">
                    <button class="hero-btn2"> Baca Dokumentasi</button>
                </a>

            </div><!--hero-left-->

            <div class="col-sm-6 col-sm-6 ipad">
                <img class="ipad-screen img-responsive" src="<?php echo $imgbase; ?>dashboard.png"/>
            </div>


        </div><!--hero-->

    </div> <!--hero-container-->

</div><!--hero-background-->



<!-- Logos
  ================================================== -->

<div class="logos-section">
    <img class="logos" src="<?php echo $imgbase; ?>logos.png"/>
</div><!--logos-section-->

<!-- White-Section
  ================================================== -->

<div class="white-section row">

    <div class="imac col-sm-6" style="margin-top: 105px; margin-bottom: 80px">
        <iframe width="100%" height="315" src="https://www.youtube.com/embed/XqcX0z63Jzo" frameborder="0" allowfullscreen></iframe>
    </div>
    <!--imac-->

    <div class="col-sm-5">

        <div class="white-section-text">

            <h2 class="imac-section-header light">SIMPLE BUT POWERFUL</h2>

            <div class="imac-section-desc">

            <span>
                Dengan menggunakan plugin OngkosKirim.id, woocommerce mu akan mendukung semua ekspedisi populer di Indonesia dalam sekejap.<br>
                Konfigurasinya sangat-sangat mudah, hanya butuh beberapa detik, dan viola .. toko online mu sudah siap.<br>
                Plugin ini dibangun oleh tim yang sudah berpengalaman dalam ecommerce belasan tahun dan telah dipercaya oleh lebih dari 100ribu pelanggan.<br>
            </span>
            </div>
        </div>
    </div>
</div><!--white-section-text-section--->


<?php
if( !$this->lib->is_license_active() ) {
    ?>
    <!-- Pricing
      ================================================== -->
    <div id="pricing" class="pricing-background">

        <h2 class="pricing-section-header light text-center">Pilihan Lisensi Plugin OngkosKirim.id</h2>
        <h4 class=" pricing-section-sub text-center light">Dapatkan fitur sangat komplit di versi PRO</h4>

        <div class="pricing-table row">
            <div class="col-sm-4">
                <div class="plan">
                    <h3 class="plan-title light">Gratis</h3>
                    <h4 class="plan-cost bold">0</h4>
                    <h5 class="monthly"></h5>
                    <ul class="plan-features">
                        <li class="plan-check">Ongkir Detail Sampai Tingkat Kecamatan</li>
                        <li class="plan-check">JNE, TIKI, POS, Sicepat, J&ampT, Wahana</li>
                        <li class="plan-stripe"><span>JNE YES, JNE OKE, TIKI ONS, Sicepat BEST</span></li>
                        <li class="plan-check">Masa Aktif Selamanya</li>
                        <li class="plan-stripe"><span>Prioritas Layanan</span></li>
                        <li class="plan-check">Untuk 1 Website</li>
                    </ul>
                    <div class="plan-price-div text-center">
                        <div class="choose-plan-div">
                            <a class="plan-btn light" href="#">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div><!--basic-plan--->
            </div><!--col-->

            <div class="col-sm-4">
                <div class="mid-plan">
                    <h3 class="plan-title light">PRO</h3>
                    <h4 class="plan-cost bold">199k</h4>
                    <h5 class="monthly"></h5>
                    <ul class="plan-features">
                        <li class="plan-check">Ongkir Detail Sampai Tingkat Kecamatan</li>
                        <li class="plan-check">JNE, TIKI, POS, Sicepat, J&ampT, Wahana</li>
                        <li class="plan-check">JNE YES, JNE OKE, TIKI ONS, Sicepat BEST</li>
                        <li class="plan-check">Masa Aktif 1 tahun</li>
                        <li class="plan-check">Prioritas Layanan</li>
                        <li class="plan-check">Untuk 1 Website</li>
                    </ul>
                    <div class="plan-price-div text-center">
                        <div class="choose-plan-div">
                            <a class="plan-btn light" href="<?php echo $this->upgrade_pro_url; ?>">
                                Upgrade Sekarang
                            </a>
                        </div>
                    </div>
                </div><!--pro-plan--->
            </div><!--col-->

            <div class="col-sm-4">
                <div class="plan">
                    <h3 class="plan-title light">PRO Multi Site</h3>
                    <h4 class="plan-cost bold">399k</h4>
                    <h5 class="monthly"></h5>
                    <ul class="plan-features">
                        <li class="plan-check">Ongkir Detail Sampai Tingkat Kecamatan</li>
                        <li class="plan-check">JNE, TIKI, POS, Sicepat, J&ampT, Wahana</li>
                        <li class="plan-check">JNE YES, JNE OKE, TIKI ONS, Sicepat BEST</li>
                        <li class="plan-check">Masa Aktif 1 tahun</li>
                        <li class="plan-check">Prioritas Layanan</li>
                        <li class="plan-check">Untuk 5 Website</li>
                    </ul>
                    <div class="plan-price-div text-center">
                        <div class="choose-plan-div">
                            <a class="plan-btn light" href="<?php echo $this->upgrade_pro_multi_url; ?>">
                                Get Started
                            </a>
                        </div>
                    </div><!--basic-plan--->
                </div><!--pro-plan--->
            </div><!--col-->

        </div>  <!--pricing-table-->

    </div><!--pricing-background-->
    <?php
}
?>
<!-- Team
  ================================================== -->


<!-- Email-Section
  ================================================== -->

<?php
if( !$this->lib->is_license_active() ) {
    ?>
    <div class="blue-section">
        <h3 class="blue-section-header bold"> Diskon 20%, Hanya Hari Ini!</h3>
        <h4 class="blue-section-subtext light">Upgrade hari ini, dan dapatkan diskon 20%! Hanya hari ini!</h4>
        <br>
        Masukkan kode kupon berikut saat checkout: <h4 class="bold">20TODAY</h4>
        <!--email-form-div-->

        <div id="newsletter-loading-div"></div>
        <!--email-form-->
    </div>
    <!--blue-section-->
    <?php
}
?>
<!-- Footer
  ================================================== -->

<div class="footer">

    <div class="container" style="width: 100%;">
        <div class="row">

            <div class="col-sm-2"></div>

            <div class="col-sm-8 webscope">
                <span class="webscope-text"> Developed By </span>
                <a href="https://jogjacamp.com"> <img src="https://jogjacamp.com/themes/jcamp2017/images/logo.png"/> </a>
            </div>
            <!--webscope-->


            <!--social-links-parent-->

        </div>
        <!--row-->

    </div>
    <!--container-->
</div>
<!--footer-->