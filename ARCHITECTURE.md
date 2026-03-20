# Architecture: statsforecast

## Purpose

A PrestaShop statistics module that projects future sales based on historical order data, giving merchants a forward-looking revenue estimate.

## Directory Structure

```
statsforecast.php   - Module class (ModuleGraph subclass); all business logic
upgrade/            - Migration scripts
tests/              - PHPUnit test stubs and PHPStan bootstrap
translations/       - Locale string overrides
```

## Key Design Decisions

- **ModuleGraph inheritance**: Renders a line chart of historical and projected sales.
- **Simple extrapolation**: Uses moving-average or linear extrapolation over configurable periods.

## Extension Points

- Override `getData()` to substitute a more sophisticated forecasting model.

## Dependency Flow

```
statsforecast (ModuleGraph)
  └─> hookDisplayAdminStatsModules() — renders the forecast chart
  └─> getData()                      — historical + projected data query
        └─> Db::getInstance()
```
