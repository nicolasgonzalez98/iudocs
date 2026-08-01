export const formatBytes = (bytes) => {
    if (!bytes) return '0 KB';
    const kb = bytes / 1024;
    if (kb < 1024) return `${Math.round(kb)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

export const fileIcon = (mime = '', name = '') => {
    const n = (name || '').toLowerCase();
    if (mime.includes('pdf') || n.endsWith('.pdf')) return '📄';
    if (mime.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)$/.test(n)) return '🖼️';
    if (/\.(docx?|odt)$/.test(n)) return '📝';
    if (/\.(pptx?)$/.test(n)) return '📊';
    if (/\.(xlsx?|csv)$/.test(n)) return '📈';
    return '📎';
};
