  </main>

  <!-- ===== MODALS ===== -->
  <?php 
  // Sayfa bazlı modal yükleme
  $currentPageForModals = $currentPage ?? '';
  $modalFiles = [
    'customers' => ['customer.php'],
    'customer' => ['customer.php', 'invoice.php', 'payment.php', 'project.php', 'meeting.php', 'contact.php', 'offer.php', 'contract.php', 'guarantee.php', 'stamp-tax.php', 'file.php'],
    'invoices' => ['invoice.php'],
    'payments' => ['payment.php'],
    'projects' => ['project.php'],
    'offers' => ['offer.php'],
    'contracts' => ['contract.php'],
    'guarantees' => ['guarantee.php'],
    'users' => ['user.php'],
    'parameters' => ['currency.php', 'status.php'],
    'dashboard' => ['customer.php']
  ];
  
  if (isset($modalFiles[$currentPageForModals])) {
    foreach ($modalFiles[$currentPageForModals] as $modalFile) {
      $modalPath = __DIR__ . '/modals/' . $modalFile;
      if (file_exists($modalPath)) {
        require $modalPath;
      }
    }
  }
  ?>

  <!-- ===== FOOTER ===== -->
  <footer class="fixed-bottom bg-dark text-white py-2 small" style="height:40px;">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-4">
          <span id="footerIp">IP: -</span>
        </div>
        <div class="col-4 text-center">
          <span id="footerUser">Kullanıcı: -</span>
        </div>
        <div class="col-4 text-end">
          <span id="footerDateTime">-</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="/assets/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
  <script src="/assets/js/pages.js"></script>

</body>
</html>
