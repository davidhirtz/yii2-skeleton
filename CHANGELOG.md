# Version v2.0.0

- Moved source code to `src` folder
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Changed namespaces from `davidhirtz\yii2\skeleton\admin\widgets\grid`
  to `davidhirtz\yii2\skeleton\admin\widgets\grids` and `davidhirtz\yii2\skeleton\admin\widgets\nav`
  to `davidhirtz\yii2\skeleton\admin\widgets\navs`
- Changed namespaces for `CounterColumn` to `davidhirtz\yii2\skeleton\admin\widgets\grids\columns`
- Changed namespaces for `MessageSourceTrait`, `StatusGridViewTrait` and `TypeGridViewTrait`
  to `davidhirtz\yii2\skeleton\admin\widgets\grids\traits`
- `TrailGridView` now tries to automatically load related models

# Version v1.9.0

- Replaced CKEditor with TinyMCE 6, because CKEditor reached its End of Life (EOL) in June 2023