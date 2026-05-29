import type { Article } from '../types';
import { ChangelogV304Article } from './changelog/v3-0-4';
import { ChangelogV303Article } from './changelog/v3-0-3';
import { ChangelogV302Article } from './changelog/v3-0-2';
import { ChangelogV301Article } from './changelog/v3-0-1';
import { ChangelogV3Article } from './changelog/v3';

export const ChangelogArticles: Article[] = [
    ChangelogV304Article,
    ChangelogV303Article,
    ChangelogV302Article,
    ChangelogV301Article,
    ChangelogV3Article,
];
