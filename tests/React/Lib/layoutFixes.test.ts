import { describe, it, expect } from 'vitest';
import { normalizeBodyBottomSpacing } from '../../../resources/js/inertia/lib/layoutFixes';

describe('normalizeBodyBottomSpacing', () => {
  it('removes forced bottom spacing from body style', () => {
    document.body.setAttribute('style', 'margin-bottom: 64px; padding-bottom: 32px;');

    normalizeBodyBottomSpacing(document.body);

    expect(document.body.style.getPropertyValue('margin-bottom')).toBe('0px');
    expect(document.body.style.getPropertyValue('padding-bottom')).toBe('0px');
  });
});
