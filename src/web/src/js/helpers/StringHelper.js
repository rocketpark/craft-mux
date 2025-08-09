/**
 * Converts a string to a handle.
 * @param {string} str
 * @returns {string}
 * @example
 * toHandle("My Awesome Field Name!"); // "myAwesomeFieldName"
 * toHandle("123 Bad Handle"); // "badHandle"
 * toHandle("café & résumé"); // "cafResum"
 * toHandle("my-field-name"); // "myFieldName"
 */
export const toHandle = (str) => {
    if (!str) return '';
    
    // Remove HTML tags
    str = str.replace(/<[^>]*>/g, '');
    
    // Remove special characters
    str = str.replace(/['"'""ʻ\[\]\(\)\{\}:]/g, '');
    
    // Convert to lowercase
    str = str.toLowerCase();
    
    // Convert extended ASCII to basic ASCII (simplified)
    str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    
    // Handle must start with a letter
    str = str.replace(/^[^a-z]+/, '');
    
    // Replace non-alphanumeric/underscore with spaces
    str = str.replace(/[^a-z0-9_]/g, ' ');
    
    // Convert to camelCase (lowercase first letter)
    return str.split(' ')
        .map((word, index) => {
            if (index === 0) {
                return word; // First word stays lowercase
            }
            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join('');
}