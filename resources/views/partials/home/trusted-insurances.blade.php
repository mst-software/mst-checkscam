@if ($trustedInsurances->isNotEmpty())
  <section aria-label="Đối tác tin cậy">
    <x-ucs.section-title
      title="Đối tác tin cậy"
      subtitle="Trung gian đã ký quỹ với CheckScam, quỹ dùng để đền bù nếu xảy ra lừa đảo."
      action-label="Xem tất cả"
      :action-url="route('insurances.frontend.index')"
    />

    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($trustedInsurances as $insurance)
        <li class="border-line bg-surface rounded-card overflow-hidden border">
          @include('partials.insurance-item', ['insurance' => $insurance])
        </li>
      @endforeach
    </ul>
  </section>
@endif
