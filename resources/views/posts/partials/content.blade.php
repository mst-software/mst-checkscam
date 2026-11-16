{{--
  Article body typography for admin-authored HTML (no typography plugin, so styles target child elements).
  Expects: $content (trusted HTML from the admin editor).
--}}
<div
  class="text-ink-muted mt-8 text-base leading-8 break-words sm:text-[17px] [&_a]:text-accent [&_a]:underline [&_a]:underline-offset-2 [&_blockquote]:border-line-strong [&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_blockquote]:italic [&_code]:bg-surface-muted [&_code]:rounded [&_code]:px-1 [&_code]:text-sm [&_figure]:my-6 [&_h1]:text-ink [&_h1]:mt-10 [&_h1]:mb-3 [&_h1]:text-2xl [&_h1]:leading-snug [&_h1]:font-semibold [&_h2]:text-ink [&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:leading-snug [&_h2]:font-semibold [&_h3]:text-ink [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold [&_h4]:text-ink [&_h4]:mt-6 [&_h4]:mb-2 [&_h4]:font-semibold [&_hr]:border-line [&_hr]:my-8 [&_img]:border-line [&_img]:rounded-card [&_img]:my-6 [&_img]:h-auto [&_img]:max-w-full [&_img]:border [&_li]:my-1.5 [&_ol]:my-5 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-5 [&_pre]:bg-surface-muted [&_pre]:rounded-control [&_pre]:my-6 [&_pre]:overflow-x-auto [&_pre]:p-4 [&_strong]:text-ink [&_strong]:font-semibold [&_table]:my-6 [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto [&_table]:text-sm [&_td]:border-line [&_td]:border [&_td]:p-2 [&_th]:border-line [&_th]:bg-surface-muted [&_th]:border [&_th]:p-2 [&_th]:text-left [&_ul]:my-5 [&_ul]:list-disc [&_ul]:pl-6 [&>*:first-child]:mt-0"
>
  {!! $content !!}
</div>
