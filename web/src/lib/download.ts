/**
 * Hands the browser a file without keeping a copy anywhere. The link is in the
 * page while clicked and the URL is revoked a moment later: revoking in the
 * same tick can cancel the download in Safari and Firefox (review L2).
 */
export function saveBlob(blob: Blob, filename: string) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
}
