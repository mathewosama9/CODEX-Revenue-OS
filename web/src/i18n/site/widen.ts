/** Same rule as the app dictionary: English must have every Arabic key, any string value. */
export type Widen<T> = { [K in keyof T]: T[K] extends string ? string : Widen<T[K]> };
