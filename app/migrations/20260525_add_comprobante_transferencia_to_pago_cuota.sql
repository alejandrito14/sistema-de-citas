-- Migración: Agregar campo comprobante_transferencia a pago_cuota
ALTER TABLE pago_cuota ADD COLUMN comprobante_transferencia VARCHAR(255) NULL AFTER observaciones;