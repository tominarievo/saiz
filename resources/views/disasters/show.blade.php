@extends('layouts.map')

@section('title', $disaster->name . ' | 災害情報')

@push('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    html,
    body {
      height: 100%;
    }

    body.map-app-body {
      overflow: hidden;
      background: #dee2e6;
    }

    .map-app {
      position: fixed;
      inset: 0;
      overflow: hidden;
      background: #dee2e6;
    }

    .map-app-map {
      position: absolute;
      inset: 0;
      z-index: 0;
    }

    #disaster-map:focus,
    #disaster-map svg:focus,
    #disaster-map .leaflet-interactive:focus {
      outline: none;
    }

    #disaster-map .leaflet-overlay-pane path:focus {
      outline: none;
      stroke: #dc3545;
      stroke-width: 4;
      stroke-dasharray: none;
      fill: none;
    }

    .map-app-empty {
      position: absolute;
      top: 50%;
      left: 50%;
      z-index: 5;
      width: min(90%, 28rem);
      padding: 1rem 1.25rem;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
      text-align: center;
    }

    .map-app-topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1200;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      height: 56px;
      padding: 0 0.75rem;
      background: rgba(255, 255, 255, 0.97);
      box-shadow: 0 1px 8px rgba(0, 0, 0, 0.16);
      backdrop-filter: blur(6px);
    }

    .map-app-icon-button,
    .map-app-sidebar-close {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      padding: 0;
      color: #1f3c88;
      background: transparent;
      border: 0;
      border-radius: 0.375rem;
      cursor: pointer;
      flex-shrink: 0;
    }

    .map-app-icon-button:hover,
    .map-app-sidebar-close:hover,
    .map-app-icon-button:focus-visible,
    .map-app-sidebar-close:focus-visible {
      background: #eef2f9;
      outline: none;
    }

    .map-app-brand {
      display: inline-flex;
      align-items: center;
      flex-shrink: 0;
    }

    .map-app-brand img {
      width: 34px;
      height: 34px;
      object-fit: contain;
      opacity: 0.9;
    }

    .map-app-title {
      flex: 1;
      min-width: 0;
      line-height: 1.2;
    }

    .map-app-title-name {
      overflow: hidden;
      font-size: 0.98rem;
      font-weight: 700;
      color: #212529;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .map-app-title-meta {
      margin-top: 0.15rem;
      overflow: hidden;
      color: #6c757d;
      font-size: 0.75rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .map-app-disaster-switcher {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      min-width: 0;
      flex-shrink: 1;
    }

    .map-app-disaster-switcher label {
      margin: 0;
      color: #6c757d;
      font-size: 0.72rem;
      white-space: nowrap;
    }

    .map-app-disaster-switcher select {
      min-width: 160px;
      max-width: 320px;
      height: 32px;
      padding: 0 1.8rem 0 0.5rem;
      color: #212529;
      background: #fff;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      font-size: 0.82rem;
    }

    .map-app-topbar-actions {
      display: flex;
      align-items: center;
      gap: 0.15rem;
      flex-shrink: 0;
    }

    .map-app-bar-link {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.4rem 0.55rem;
      color: #1f3c88;
      background: transparent;
      border: 0;
      border-radius: 0.375rem;
      font-size: 0.85rem;
      text-decoration: none;
      white-space: nowrap;
      cursor: pointer;
    }

    .map-app-bar-link:hover,
    .map-app-bar-link:focus-visible {
      color: #12285f;
      background: #eef2f9;
      text-decoration: none;
      outline: none;
    }

    .map-app-sidebar {
      position: fixed;
      top: 56px;
      bottom: 0;
      left: 0;
      z-index: 1100;
      display: flex;
      flex-direction: column;
      width: 380px;
      max-width: 90vw;
      background: #fff;
      box-shadow: 3px 0 16px rgba(0, 0, 0, 0.2);
      transform: translateX(-105%);
      transition: transform 0.25s ease;
    }

    .map-app-sidebar.is-open {
      transform: translateX(0);
    }

    .map-app-sidebar-resizer {
      position: absolute;
      top: 0;
      right: -6px;
      width: 6px;
      height: 100%;
      z-index: 2;
      cursor: col-resize;
      touch-action: none;
    }

    .map-app-sidebar-resizer::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      width: 2px;
      height: 44px;
      margin-top: -22px;
      background: #cfd4da;
      border-radius: 2px;
      transition: background 0.15s ease;
    }

    .map-app-sidebar-resizer:hover::after,
    .map-app-sidebar-resizer.is-resizing::after,
    .map-app-sidebar-resizer:focus-visible::after {
      background: #17a2b8;
    }

    body.map-app-resizing {
      cursor: col-resize;
      user-select: none;
    }

    .shelter-marker span {
      display: block;
      width: 14px;
      height: 14px;
      background: #17a2b8;
      border: 2px solid #fff;
      border-radius: 50%;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25), 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .shelter-marker-emergency span {
      background: #fd7e14;
    }

    .shelter-marker-active span {
      width: 18px;
      height: 18px;
      background: #dc3545;
      border: 3px solid #fff;
      box-shadow: 0 0 0 2px #dc3545, 0 0 0 6px rgba(220, 53, 69, 0.22);
      animation: shelter-active-pulse 1.8s ease-out infinite;
    }

    @keyframes shelter-active-pulse {
      0% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 2px rgba(220, 53, 69, 0.35);
      }
      70% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 12px rgba(220, 53, 69, 0);
      }
      100% {
        box-shadow: 0 0 0 2px #dc3545, 0 0 0 2px rgba(220, 53, 69, 0);
      }
    }

    .volunteer-center-marker span {
      display: block;
      width: 16px;
      height: 16px;
      background: #28a745;
      border: 2px solid #fff;
      border-radius: 4px;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25), 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .map-popup-navigation {
      display: inline-block;
      margin-top: 0.55rem;
      padding: 0.35rem 0.8rem;
      background: #007bff;
      color: #fff !important;
      border-radius: 4px;
      font-weight: 600;
      text-decoration: none !important;
    }

    .map-popup-navigation:hover,
    .map-popup-navigation:focus {
      background: #0056b3;
      color: #fff !important;
    }

    .map-app-sidebar-header {
      position: relative;
      flex-shrink: 0;
      padding: 1rem 1.1rem 0.85rem;
      border-bottom: 1px solid #e9ecef;
    }

    .map-app-sidebar-eyebrow {
      color: #17a2b8;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .map-app-sidebar-title {
      margin: 0.25rem 0 0.5rem;
      padding-right: 2rem;
      font-size: 1.25rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .map-app-sidebar-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
    }

    .map-app-sidebar-close {
      position: absolute;
      top: 0.65rem;
      right: 0.55rem;
    }

    .map-app-tabs {
      display: flex;
      flex-shrink: 0;
      overflow-x: auto;
      background: #f8f9fa;
      border-bottom: 1px solid #e9ecef;
      scrollbar-width: none;
    }

    .map-app-tabs::-webkit-scrollbar {
      display: none;
    }

    .map-app-tab {
      flex: 0 0 auto;
      min-width: 3.45rem;
      padding: 0.7rem 0.3rem;
      color: #6c757d;
      background: transparent;
      border: 0;
      border-bottom: 2px solid transparent;
      font-size: 0.8rem;
      font-weight: 600;
      white-space: nowrap;
      cursor: pointer;
    }

    .map-app-tab:hover,
    .map-app-tab:focus-visible {
      color: #1f3c88;
      outline: none;
    }

    .map-app-tab.is-active {
      color: #1f3c88;
      background: #fff;
      border-bottom-color: #17a2b8;
    }

    .map-app-tab-count {
      display: inline-block;
      min-width: 1.25rem;
      margin-left: 0.2rem;
      padding: 0 0.3rem;
      color: #fff;
      background: #17a2b8;
      border-radius: 999px;
      font-size: 0.7rem;
      line-height: 1.25rem;
      text-align: center;
    }

    .map-app-sidebar-body {
      flex: 1;
      overflow-y: auto;
      overscroll-behavior: contain;
      -webkit-overflow-scrolling: touch;
    }

    .map-app-panel {
      padding: 1rem 1.1rem 1.5rem;
    }

    .map-app-panel[hidden] {
      display: none;
    }

    .map-app-detail-list {
      margin: 0;
    }

    .map-app-detail-row {
      display: grid;
      grid-template-columns: 5.5rem 1fr;
      gap: 0.65rem;
      padding: 0.55rem 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .map-app-detail-row dt {
      margin: 0;
      color: #6c757d;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .map-app-detail-row dd {
      margin: 0;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    .map-app-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .map-app-news-item {
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .map-app-news-item:first-child {
      padding-top: 0;
    }

    .map-app-news-item p {
      margin: 0 0 0.4rem;
      font-size: 0.88rem;
      line-height: 1.55;
    }

    .map-app-news-meta {
      margin-bottom: 0.25rem;
      color: #6c757d;
      font-size: 0.7rem;
    }

    .map-app-news-item a {
      font-size: 0.78rem;
      word-break: break-all;
    }

    .map-layer-title {
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    .map-layer-control details {
      margin-bottom: 0.5rem;
    }

    .map-layer-control summary {
      color: #1f3c88;
      font-weight: 600;
      cursor: pointer;
    }

    .map-layer-option {
      display: block;
      margin: 0.35rem 0 0.35rem 1rem;
      font-size: 0.88rem;
      cursor: pointer;
    }

    .map-layer-status {
      margin-top: 0.35rem;
      color: #6c757d;
      font-size: 0.78rem;
      line-height: 1.4;
    }

    .map-layer-note {
      margin: 0.75rem 0 0;
      color: #6c757d;
      font-size: 0.75rem;
      line-height: 1.5;
    }

    .support-panel {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .support-section-header {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      padding-bottom: 0.35rem;
      border-bottom: 1px solid #e9ecef;
    }

    .support-section-header strong {
      color: #1f3c88;
      font-size: 0.9rem;
    }

    .support-section-header span {
      color: #6c757d;
      font-size: 0.72rem;
    }

    .support-list {
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
    }

    .support-empty {
      margin: 0;
      color: #6c757d;
      font-size: 0.78rem;
    }

    .support-item {
      display: block;
      width: 100%;
      padding: 0.55rem 0.6rem;
      border: 1px solid #e9ecef;
      border-radius: 0.4rem;
      background: #fff;
      color: #212529;
      text-align: left;
      cursor: pointer;
    }

    .support-item:hover,
    .support-item:focus-visible {
      border-color: #17a2b8;
      background: #f1fbfd;
      outline: none;
    }

    .support-item-name {
      display: block;
      margin-top: 0.2rem;
      font-size: 0.82rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .support-item-meta {
      display: block;
      margin-top: 0.15rem;
      color: #6c757d;
      font-size: 0.72rem;
      line-height: 1.4;
    }

    .support-badge {
      display: inline-block;
      padding: 0.08rem 0.38rem;
      border-radius: 0.25rem;
      color: #fff;
      font-size: 0.66rem;
      font-weight: 700;
      line-height: 1.4;
    }

    .support-meeting {
      padding: 0.65rem 0.7rem;
      border: 1px solid #e9ecef;
      border-radius: 0.4rem;
      background: #fff;
    }

    .support-meeting-title {
      display: block;
      color: #1f3c88;
      font-size: 0.82rem;
      font-weight: 700;
      line-height: 1.4;
    }

    .support-meeting-block {
      margin-top: 0.45rem;
      font-size: 0.74rem;
      line-height: 1.45;
    }

    .support-meeting-block strong {
      display: block;
      margin-bottom: 0.1rem;
      color: #495057;
    }

    .support-meeting-block ul {
      margin: 0;
      padding-left: 1.1rem;
    }

    .support-meeting-block li + li {
      margin-top: 0.1rem;
    }

    .support-meeting-source {
      margin-top: 0.45rem;
      padding-top: 0.4rem;
      border-top: 1px solid #f1f3f5;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.45;
    }

    .population-municipality + .population-municipality {
      margin-top: 1.25rem;
      padding-top: 1rem;
      border-top: 1px solid #e9ecef;
    }

    .population-municipality > summary {
      list-style: none;
      cursor: pointer;
    }

    .population-municipality > summary::-webkit-details-marker {
      display: none;
    }

    .population-municipality > summary::before {
      content: '▶';
      flex-shrink: 0;
      color: #1f3c88;
      font-size: 0.65rem;
      transition: transform 0.15s ease;
    }

    .population-municipality[open] > summary::before {
      transform: rotate(90deg);
    }

    .population-municipality > summary:focus-visible {
      outline: 2px solid #17a2b8;
      outline-offset: 2px;
    }

    .population-header {
      display: flex;
      align-items: baseline;
      gap: 0.5rem;
    }

    .population-header .population-title {
      margin: 0;
      font-size: 1.02rem;
      font-weight: 700;
    }

    .population-header .population-date {
      margin-left: auto;
      color: #6c757d;
      font-size: 0.7rem;
      white-space: nowrap;
    }

    .population-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.45rem;
      margin: 0.7rem 0;
    }

    .population-stat {
      padding: 0.5rem 0.6rem;
      background: #f8f9fa;
      border-radius: 0.375rem;
    }

    .population-stat-label {
      display: block;
      color: #6c757d;
      font-size: 0.7rem;
    }

    .population-stat-value {
      font-size: 1rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
    }

    .population-source {
      margin: 0.25rem 0 0;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.4;
      word-break: break-all;
    }

    .population-section-title {
      margin: 1rem 0 0.45rem;
      font-size: 0.88rem;
      font-weight: 700;
    }

    .population-section-title small {
      color: #6c757d;
      font-size: 0.7rem;
      font-weight: 400;
    }

    .population-age-list {
      display: flex;
      flex-direction: column;
      gap: 0.22rem;
    }

    .population-age-row {
      display: grid;
      grid-template-columns: 4.2rem 1fr 3.2rem;
      gap: 0.4rem;
      align-items: center;
      font-size: 0.76rem;
    }

    .population-age-label {
      color: #495057;
      white-space: nowrap;
    }

    .population-age-bar {
      height: 0.55rem;
      overflow: hidden;
      background: #e9ecef;
      border-radius: 999px;
    }

    .population-age-bar span {
      display: block;
      height: 100%;
      background: #17a2b8;
      border-radius: 999px;
    }

    .population-age-total {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    .population-age-foreigners {
      grid-column: 2 / 4;
      margin-top: -0.15rem;
      color: #6c757d;
      font-size: 0.68rem;
      text-align: right;
    }

    .population-small-areas {
      margin-top: 1rem;
    }

    .population-small-areas summary {
      color: #1f3c88;
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
    }

    .population-table-wrap {
      max-height: 260px;
      margin-top: 0.5rem;
      overflow: auto;
      border: 1px solid #e9ecef;
      border-radius: 0.375rem;
    }

    .population-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.74rem;
    }

    .population-table th,
    .population-table td {
      padding: 0.35rem 0.45rem;
      border-bottom: 1px solid #f1f3f5;
      white-space: nowrap;
    }

    .population-table th {
      position: sticky;
      top: 0;
      background: #f8f9fa;
      text-align: left;
    }

    .population-table td.number {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    .population-empty {
      color: #6c757d;
      font-size: 0.85rem;
      line-height: 1.5;
    }

    .population-mesh-note {
      margin: 0.75rem 0 0;
      color: #6c757d;
      font-size: 0.7rem;
      line-height: 1.4;
    }

    .map-app-phase-badge {
      display: inline-block;
      padding: 0.14rem 0.5rem;
      color: #fff;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 700;
      line-height: 1.5;
      white-space: nowrap;
    }

    .map-app-phase-badge.is-danger {
      background: #b02a37;
    }

    .map-app-phase-badge.is-success {
      background: #1e7e34;
    }

    .map-app-phase-badge.is-warning {
      color: #212529;
      background: #ffc107;
    }

    .map-app-phase-badge.is-secondary {
      background: #5a6268;
    }

    .map-app-last-updated {
      color: #495057;
      font-size: 0.7rem;
      white-space: nowrap;
    }

    .map-app-presets {
      display: flex;
      flex-shrink: 0;
      gap: 0.3rem;
      padding: 0.5rem 0.65rem;
      overflow-x: auto;
      background: #fff;
      border-bottom: 1px solid #e9ecef;
      scrollbar-width: none;
    }

    .map-app-presets::-webkit-scrollbar {
      display: none;
    }

    .map-app-preset {
      flex: 0 0 auto;
      padding: 0.35rem 0.55rem;
      color: #1f3c88;
      background: #f1f5fb;
      border: 1px solid #c9d6ee;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      white-space: nowrap;
      cursor: pointer;
    }

    .map-app-preset:hover,
    .map-app-preset:focus-visible {
      background: #e3ecfa;
      outline: 2px solid #17a2b8;
      outline-offset: 1px;
    }

    .map-app-preset[aria-pressed="true"] {
      color: #fff;
      background: #1f3c88;
      border-color: #1f3c88;
    }

    .map-municipality-filter {
      margin-bottom: 0.45rem;
    }

    .map-municipality-list {
      max-height: 15rem;
      overflow-y: auto;
      padding-right: 0.15rem;
    }

    .map-municipality-group {
      margin-bottom: 0.35rem;
    }

    .map-municipality-group > summary {
      color: #1f3c88;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
    }

    .map-municipality-group > summary span {
      color: #6c757d;
      font-size: 0.68rem;
      font-weight: 400;
    }

    .map-municipality-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
      margin-top: 0.35rem;
    }

    .map-municipality-tag {
      padding: 0.2rem 0.5rem;
      color: #212529;
      background: #fff;
      border: 1px solid #ced4da;
      border-radius: 999px;
      font-size: 0.75rem;
      line-height: 1.4;
      cursor: pointer;
    }

    .map-municipality-tag:hover,
    .map-municipality-tag:focus-visible,
    .map-municipality-tag.is-hovered {
      color: #fff;
      background: #17a2b8;
      border-color: #17a2b8;
      outline: none;
    }

    .map-municipality-tag.is-selected {
      color: #fff;
      background: #b02a37;
      border-color: #b02a37;
      box-shadow: 0 0 0 2px rgba(176, 42, 55, 0.2);
    }

    .map-municipality-empty {
      margin: 0;
      color: #6c757d;
      font-size: 0.78rem;
    }

    .map-app-detail-row-municipalities {
      grid-template-columns: 5.5rem 1fr;
    }

    .map-app-digest {
      margin-top: 1rem;
      padding: 0.75rem;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 0.5rem;
    }

    .map-app-digest-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.4rem;
    }

    .map-app-digest-head strong {
      color: #1f3c88;
      font-size: 0.86rem;
    }

    .map-app-digest-more {
      padding: 0.15rem 0.4rem;
      color: #1f3c88;
      background: transparent;
      border: 0;
      font-size: 0.72rem;
      font-weight: 600;
      cursor: pointer;
    }

    .map-app-digest-more:hover,
    .map-app-digest-more:focus-visible {
      text-decoration: underline;
      outline: none;
    }

    .map-app-digest-item {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 0.15rem 0.45rem;
      width: 100%;
      padding: 0.4rem 0;
      color: #212529;
      background: transparent;
      border: 0;
      border-top: 1px solid #e9ecef;
      text-align: left;
      cursor: pointer;
    }

    .map-app-digest-item:hover,
    .map-app-digest-item:focus-visible {
      color: #1f3c88;
      outline: none;
    }

    .map-app-digest-type {
      color: #6c757d;
      font-size: 0.65rem;
      font-weight: 700;
    }

    .map-app-digest-title {
      overflow: hidden;
      font-size: 0.78rem;
      font-weight: 600;
      line-height: 1.4;
      text-overflow: ellipsis;
    }

    .map-app-digest-date {
      grid-column: 2;
      color: #6c757d;
      font-size: 0.67rem;
    }

    .map-app-digest-empty {
      margin: 0;
      color: #6c757d;
      font-size: 0.75rem;
    }

    .map-app-news-item.is-focused {
      animation: map-news-focus 2.2s ease-out;
    }

    @keyframes map-news-focus {
      0%,
      60% {
        background: #fff3cd;
      }
      100% {
        background: transparent;
      }
    }

    .map-quick-panel {
      position: fixed;
      top: 140px;
      right: 12px;
      z-index: 1050;
      width: 236px;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.97);
      border: 1px solid rgba(31, 60, 136, 0.18);
      border-radius: 0.5rem;
      box-shadow: 0 6px 22px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(6px);
    }

    .map-quick-toggle {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      width: 100%;
      padding: 0.55rem 0.65rem;
      color: #1f3c88;
      background: transparent;
      border: 0;
      font-size: 0.8rem;
      font-weight: 700;
      text-align: left;
      cursor: pointer;
    }

    .map-quick-toggle:hover,
    .map-quick-toggle:focus-visible {
      background: #eef2f9;
      outline: none;
    }

    .map-quick-chevron {
      margin-left: auto;
      transition: transform 0.2s ease;
    }

    .map-quick-panel.is-collapsed .map-quick-chevron {
      transform: rotate(180deg);
    }

    .map-quick-panel.is-collapsed .map-quick-body {
      display: none;
    }

    .map-quick-body {
      max-height: calc(100vh - 180px);
      padding: 0 0.65rem 0.65rem;
      overflow-y: auto;
    }

    .map-quick-layers {
      display: flex;
      flex-direction: column;
      gap: 0.22rem;
      padding-bottom: 0.45rem;
      border-bottom: 1px solid #e9ecef;
    }

    .map-quick-layers label {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      margin: 0;
      font-size: 0.78rem;
      cursor: pointer;
    }

    .map-quick-legend {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      padding: 0.45rem 0;
      color: #495057;
      font-size: 0.7rem;
      line-height: 1.35;
    }

    .map-quick-legend-row {
      display: flex;
      align-items: flex-start;
      gap: 0.35rem;
    }

    .map-quick-legend-swatch {
      flex-shrink: 0;
      width: 0.65rem;
      height: 0.65rem;
      margin-top: 0.15rem;
      border-radius: 999px;
    }

    .map-quick-legend-swatch.is-line {
      height: 0.18rem;
      margin-top: 0.42rem;
      border-radius: 0;
    }

    .map-quick-legend-empty {
      color: #6c757d;
    }

    .map-quick-actions {
      display: flex;
      gap: 0.35rem;
      padding-top: 0.45rem;
      border-top: 1px solid #e9ecef;
    }

    .map-quick-action {
      flex: 1;
      padding: 0.35rem 0.3rem;
      color: #1f3c88;
      background: #f1f5fb;
      border: 1px solid #c9d6ee;
      border-radius: 0.35rem;
      font-size: 0.7rem;
      font-weight: 700;
      cursor: pointer;
    }

    .map-quick-action:hover,
    .map-quick-action:focus-visible {
      background: #e3ecfa;
      outline: 2px solid #17a2b8;
      outline-offset: 1px;
    }

    .map-sheet-handle {
      display: none;
    }

    .map-toast {
      position: fixed;
      left: 50%;
      bottom: 24px;
      z-index: 20000;
      max-width: calc(100vw - 2rem);
      padding: 0.55rem 0.9rem;
      transform: translateX(-50%);
      color: #fff;
      background: #212529;
      border-radius: 0.4rem;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
      font-size: 0.82rem;
      font-weight: 600;
    }

    .map-toast[hidden] {
      display: none;
    }

    .map-gps-marker span {
      display: block;
      width: 16px;
      height: 16px;
      background: #0d6efd;
      border: 3px solid #fff;
      border-radius: 50%;
      box-shadow: 0 0 0 2px #0d6efd, 0 0 0 8px rgba(13, 110, 253, 0.22);
    }

    .map-locate-button {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 34px;
      height: 34px;
      padding: 0;
      color: #1f3c88;
      background: #fff;
      border: 0;
      border-radius: 2px;
      font-size: 1rem;
      cursor: pointer;
    }

    .map-locate-button:hover,
    .map-locate-button:focus-visible {
      color: #12285f;
      background: #eef2f9;
      outline: 2px solid #17a2b8;
      outline-offset: -2px;
    }

    .leaflet-top.leaflet-right {
      top: 64px;
    }

    @media (max-width: 767.98px) {
      .map-app-topbar {
        height: 52px;
        gap: 0.5rem;
        padding: 0 0.5rem;
      }

      .map-app-sidebar {
        top: auto;
        right: 0;
        bottom: 0;
        left: 0;
        width: 100%;
        max-width: none;
        height: calc(100vh - 52px);
        border-radius: 1rem 1rem 0 0;
        box-shadow: 0 -8px 28px rgba(0, 0, 0, 0.25);
        transform: translateY(calc(100% - 44px));
        transition: transform 0.25s ease;
      }

      .map-app-sidebar.is-open {
        transform: translateY(0);
      }

      .map-app-sidebar[data-sheet-state="minimized"] {
        transform: translateY(calc(100% - 44px));
      }

      .map-app-sidebar[data-sheet-state="half"] {
        transform: translateY(50%);
      }

      .map-app-sidebar[data-sheet-state="full"] {
        transform: translateY(0);
      }

      .map-app-sidebar-resizer {
        display: none;
      }

      .map-sheet-handle {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        height: 44px;
        cursor: grab;
        touch-action: none;
      }

      .map-sheet-handle:active {
        cursor: grabbing;
      }

      .map-sheet-handle span {
        width: 2.6rem;
        height: 0.32rem;
        background: #adb5bd;
        border-radius: 999px;
      }

      .map-sheet-handle:focus-visible {
        outline: 2px solid #17a2b8;
        outline-offset: -4px;
      }

      .map-app-presets {
        padding: 0.4rem 0.5rem;
      }

      .map-app-preset {
        font-size: 0.68rem;
      }

      .map-quick-panel {
        top: 134px;
        right: 8px;
        width: min(228px, calc(100vw - 16px));
      }

      .map-quick-panel.is-collapsed {
        width: auto;
      }

      .map-quick-panel.is-collapsed .map-quick-toggle span {
        display: none;
      }

      .map-app-title {
        display: none;
      }

      .map-app-title-meta {
        display: none;
      }

      .map-app-disaster-switcher {
        flex: 1;
      }

      .map-app-disaster-switcher label {
        display: none;
      }

      .map-app-disaster-switcher select {
        width: 100%;
        min-width: 0;
        max-width: none;
      }

      .map-app-topbar-actions .map-app-bar-link span {
        display: none;
      }

      .leaflet-top.leaflet-right {
        top: 60px;
      }

      .map-toast {
        bottom: calc(44px + 1rem);
      }
    }
  </style>
@endpush

@section('content')

  <div class="map-app" id="map-app">

    <div id="disaster-map" class="map-app-map"></div>

    <section class="map-quick-panel" id="map-quick-panel" aria-label="クイックレイヤー切替">
      <button
        type="button"
        class="map-quick-toggle"
        id="map-quick-toggle"
        aria-controls="map-quick-body"
        aria-expanded="true"
      >
        <i class="fas fa-layer-group" aria-hidden="true"></i>
        <span>レイヤー</span>
        <i class="fas fa-chevron-up map-quick-chevron" aria-hidden="true"></i>
      </button>
      <div class="map-quick-body" id="map-quick-body">
        <div class="map-quick-layers">
          <label><input type="checkbox" data-quick-layer="municipalities"> 行政区域</label>
          <label><input type="checkbox" data-quick-layer="shelters"> 避難所</label>
          <label><input type="checkbox" data-quick-layer="roads"> 道路規制</label>
          <label><input type="checkbox" data-quick-layer="waste_sites"> 仮置場</label>
          <label><input type="checkbox" data-quick-layer="volunteer_centers"> ボランティア</label>
        </div>
        <div class="map-quick-legend" id="map-quick-legend" aria-live="polite"></div>
        <div class="map-quick-actions">
          <button type="button" class="map-quick-action" id="map-geolocate-button">
            <i class="fas fa-location-arrow" aria-hidden="true"></i> 現在地へ
          </button>
          <button type="button" class="map-quick-action" id="map-share-quick-button">
            <i class="fas fa-link" aria-hidden="true"></i> 共有
          </button>
        </div>
      </div>
    </section>

    <div class="map-toast" id="map-toast" role="status" aria-live="polite" hidden></div>

    @if ($disaster->localGovernments->isEmpty())
      <div class="map-app-empty">
        <p class="mb-0">市町村が登録されていないため、地図を表示できません。</p>
      </div>
    @endif

    <header class="map-app-topbar">
      <button type="button" class="map-app-icon-button" id="sidebar-toggle" aria-controls="map-sidebar" aria-expanded="true" aria-label="メニューを開閉">
        <i class="fas fa-bars"></i>
      </button>

      <a href="{{ url('/') }}" class="map-app-brand" aria-label="トップへ">
        <img src="{{ asset('img/logo2.png') }}" alt="災図">
      </a>

      <div class="map-app-title">
        <div class="map-app-title-name">{{ $disaster->name }}</div>
        <div class="map-app-title-meta">
          <span class="map-app-phase-badge is-{{ $disasterPhase['class'] }}">{{ $disasterPhase['label'] }}</span>
          @if (filled($disaster->event_date))
            <span class="mx-1">/</span>{{ $disaster->event_date }}
          @endif
          @if (filled($disaster->npo_col_2))
            <span class="mx-1">/</span>{{ $disaster->npo_col_2 }}
          @endif
          @if ($latestUpdatedAt)
            <span class="map-app-last-updated">最終更新 {{ $latestUpdatedAt->format('Y/m/d H:i') }}</span>
          @endif
        </div>
      </div>

      <div class="map-app-disaster-switcher">
        <label for="disaster-switcher">災害切替</label>
        <select
          id="disaster-switcher"
          aria-label="災害を切り替え"
          onchange="if (this.value) { window.location.href = '{{ url('/disasters') }}/' + encodeURIComponent(this.value); }"
        >
          @if (!$disasterOptions->contains('id', $disaster->id))
            <option value="{{ $disaster->id }}" selected disabled>{{ $disaster->name }}（現在の災害）</option>
          @endif
          @foreach ($disasterOptions as $option)
            <option value="{{ $option['id'] }}" @selected($option['id'] === $disaster->id)>
              {{ $option['name'] }}@if (filled($option['event_date']))（{{ $option['event_date'] }}）@endif
            </option>
          @endforeach
        </select>
      </div>

      <div class="map-app-topbar-actions">
        <button type="button" class="map-app-bar-link" id="map-share-button" aria-label="現在の表示状態のURLをコピー">
          <i class="fas fa-link"></i><span>表示を共有</span>
        </button>
        @auth
          @if (auth()->user()->isAdmin())
            <a href="{{ route('disasters.index') }}" class="map-app-bar-link">
              <i class="fas fa-list"></i><span>災害一覧</span>
            </a>
          @endif
          <a href="{{ route('home') }}" class="map-app-bar-link">
            <i class="fas fa-home"></i><span>ホーム</span>
          </a>
          <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="map-app-bar-link">
              <i class="fas fa-sign-out-alt"></i><span>ログアウト</span>
            </button>
          </form>
        @else
          <a href="{{ route('terms') }}" class="map-app-bar-link">
            <i class="fas fa-file-alt"></i><span>利用規約</span>
          </a>
          <a href="{{ route('privacy') }}" class="map-app-bar-link">
            <i class="fas fa-user-shield"></i><span>プライバシー</span>
          </a>
          <a href="{{ route('login') }}" class="map-app-bar-link">
            <i class="fas fa-sign-in-alt"></i><span>ログイン</span>
          </a>
          @if (config('signup.enabled'))
            <a href="{{ route('signup.create') }}" class="btn btn-primary btn-sm">
              新規登録
            </a>
          @endif
        @endauth
      </div>
    </header>

    <aside class="map-app-sidebar" id="map-sidebar" aria-label="災害情報メニュー" data-sheet-state="minimized">
      <div class="map-sheet-handle" id="map-sheet-handle" role="button" tabindex="0" aria-label="パネルの高さを変更">
        <span></span>
      </div>
      <div class="map-app-sidebar-header">
        <div class="map-app-sidebar-eyebrow">災害情報</div>
        <h1 class="map-app-sidebar-title">{{ $disaster->name }}</h1>
        <div class="map-app-sidebar-meta">
          <span class="map-app-phase-badge is-{{ $disasterPhase['class'] }}">{{ $disasterPhase['label'] }}</span>
          @if ($latestUpdatedAt)
            <span class="badge badge-light border">最終更新 {{ $latestUpdatedAt->format('Y/m/d H:i') }}</span>
          @endif
          @if ($disaster->is_catastrophic_disaster)
            <span class="badge badge-danger">激甚災害</span>
          @endif
          @if (filled($disaster->npo_col_2))
            <span class="badge badge-light border">{{ $disaster->npo_col_2 }}</span>
          @endif
          @if (filled($disaster->event_date))
            <span class="badge badge-light border">{{ $disaster->event_date }}</span>
          @endif
        </div>
        <button type="button" class="map-app-sidebar-close" id="sidebar-close" aria-label="メニューを閉じる">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="map-app-presets" aria-label="利用目的別の表示プリセット">
        <button type="button" class="map-app-preset" data-map-preset="resident" aria-pressed="false">
          <i class="fas fa-house-damage" aria-hidden="true"></i> 被災住民
        </button>
        <button type="button" class="map-app-preset" data-map-preset="volunteer" aria-pressed="false">
          <i class="fas fa-hands-helping" aria-hidden="true"></i> ボランティア・支援
        </button>
        <button type="button" class="map-app-preset" data-map-preset="analysis" aria-pressed="false">
          <i class="fas fa-chart-bar" aria-hidden="true"></i> 調査・分析
        </button>
      </div>

      <div class="map-app-tabs" role="tablist" aria-label="表示切替">
        <button type="button" class="map-app-tab is-active" data-map-panel="detail" role="tab" aria-selected="true">
          災害情報
        </button>
        <button type="button" class="map-app-tab" data-map-panel="news" role="tab" aria-selected="false">
          最新情報
          @if ($disaster->informationLinks->isNotEmpty() || ($topInformation ?? collect())->isNotEmpty() || ($wasteNotices ?? collect())->isNotEmpty())
            <span class="map-app-tab-count">{{ $disaster->informationLinks->count() + ($topInformation ?? collect())->count() + ($wasteNotices ?? collect())->count() }}</span>
          @endif
        </button>
        <button type="button" class="map-app-tab" data-map-panel="population" role="tab" aria-selected="false">
          人口
        </button>
        <button type="button" class="map-app-tab" data-map-panel="support" role="tab" aria-selected="false">
          支援
          <span id="support-tab-count" class="map-app-tab-count" hidden></span>
        </button>
        <button type="button" class="map-app-tab" data-map-panel="layers" role="tab" aria-selected="false">
          レイヤー
        </button>
      </div>

      <div class="map-app-sidebar-body">

        <section class="map-app-panel" data-map-panel-content="detail" role="tabpanel">
          <dl class="map-app-detail-list">
            <div class="map-app-detail-row">
              <dt>年度</dt>
              <dd>{{ filled($disaster->npo_col_2) ? $disaster->npo_col_2 : '-' }}</dd>
            </div>
            <div class="map-app-detail-row">
              <dt>発生年月日</dt>
              <dd>{{ filled($disaster->event_date) ? $disaster->event_date : '-' }}</dd>
            </div>
            <div class="map-app-detail-row">
              <dt>災害種別</dt>
              <dd>
                @forelse ($disaster->disasterTypes as $disasterType)
                  <span class="badge badge-light border">{{ $disasterType->name }}</span>
                @empty
                  <span class="text-muted">未登録</span>
                @endforelse
              </dd>
            </div>
            <div class="map-app-detail-row map-app-detail-row-municipalities">
              <dt>市町村</dt>
              <dd>
                @if ($disaster->localGovernments->isEmpty())
                  <span class="text-muted">未登録</span>
                @else
                  <div class="map-municipality-filter">
                    <label class="sr-only" for="municipality-search">市町村名で絞り込む</label>
                    <input
                      type="search"
                      id="municipality-search"
                      class="form-control form-control-sm"
                      placeholder="市町村名で絞り込む"
                      autocomplete="off"
                    >
                  </div>
                  <div id="municipality-list" class="map-municipality-list">
                    @foreach ($disaster->localGovernments->groupBy(fn ($localGovernment) => $localGovernment->prefecture->name) as $prefectureName => $municipalities)
                      <details class="map-municipality-group" open>
                        <summary>
                          {{ $prefectureName }}
                          <span>{{ $municipalities->count() }}市町村</span>
                        </summary>
                        <div class="map-municipality-tags">
                          @foreach ($municipalities as $localGovernment)
                            <button
                              type="button"
                              class="map-municipality-tag"
                              data-municipality-code="{{ substr((string) $localGovernment->code, 0, 5) }}"
                              aria-pressed="false"
                              title="{{ $localGovernment->prefecture->name }} {{ $localGovernment->name }}"
                            >
                              {{ $localGovernment->name }}
                            </button>
                          @endforeach
                        </div>
                      </details>
                    @endforeach
                    <p class="map-municipality-empty" id="municipality-empty" hidden>該当する市町村がありません。</p>
                  </div>
                @endif
              </dd>
            </div>
            <div class="map-app-detail-row">
              <dt>備考</dt>
              <dd>
                @if (filled($disaster->npo_col_3))
                  {!! nl2br(e($disaster->npo_col_3)) !!}
                @else
                  <span class="text-muted">-</span>
                @endif
              </dd>
            </div>
          </dl>

          <section class="map-app-digest" aria-labelledby="map-app-digest-title">
            <div class="map-app-digest-head">
              <strong id="map-app-digest-title">最新情報</strong>
              <button type="button" class="map-app-digest-more" data-map-panel-link="news">すべて見る</button>
            </div>
            @forelse ($digestItems as $digestItem)
              <button
                type="button"
                class="map-app-digest-item"
                data-map-panel-link="news"
                data-focus-target="{{ $digestItem['target'] }}"
              >
                <span class="map-app-digest-type">{{ $digestItem['type'] }}</span>
                <span class="map-app-digest-title">{{ $digestItem['title'] }}</span>
                <span class="map-app-digest-date">{{ $digestItem['date'] ? $digestItem['date']->format('Y/m/d H:i') : '' }}</span>
              </button>
            @empty
              <p class="map-app-digest-empty">最新情報はまだありません。</p>
            @endforelse
          </section>

          <div class="map-app-actions">
            <a href="{{ route('disasters.intelligence', ['disaster' => $disaster->id]) }}" class="btn btn-outline-primary">
              <i class="fas fa-truck-ramp-box"></i> 専門支援情報
            </a>
            <a href="{{ route('disasters.sources', ['disaster' => $disaster->id]) }}" class="btn btn-outline-secondary">
              <i class="fas fa-book-open"></i> データ出典
            </a>
            @auth
              @if (auth()->user()->isAdmin())
                <a href="{{ route('disasters.edit', ['disaster' => $disaster->id]) }}" class="btn btn-info">
                  <i class="fas fa-edit"></i> 編集
                </a>
                <a href="{{ route('disasters.index') }}" class="btn btn-default">
                  一覧に戻る
                </a>
              @else
                <a href="{{ route('home') }}" class="btn btn-default">
                  ホームに戻る
                </a>
              @endif
            @else
              <a href="{{ route('login') }}" class="btn btn-primary">
                ログイン
              </a>
              @if (config('signup.enabled'))
                <a href="{{ route('signup.create') }}" class="btn btn-outline-secondary">
                  新規利用登録
                </a>
              @endif
              <a href="{{ url('/') }}" class="btn btn-default">
                トップに戻る
              </a>
            @endauth
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="news" role="tabpanel" hidden>
          @php($announcements = $topInformation ?? collect())
          @if ($announcements->isNotEmpty())
            @foreach ($announcements as $item)
              <article class="map-app-news-item" id="news-announcement-{{ $item->id }}">
                <div class="map-app-news-meta">お知らせ: {{ $item->published_at?->format('Y/m/d') }}</div>
                <p class="font-weight-bold">{{ $item->title }}</p>
                <p>{{ \Illuminate\Support\Str::limit(strip_tags($item->content ?? ''), 200) }}</p>
              </article>
            @endforeach
            <hr>
          @endif
          @if (($wasteNotices ?? collect())->isNotEmpty())
            @foreach ($wasteNotices as $wasteNotice)
              @php($noticeDate = $wasteNotice->closed_at ?? $wasteNotice->last_verified_at ?? $wasteNotice->updated_at)
              @php($noticeUrl = $wasteNotice->status_source_url ?: $wasteNotice->source_url)
              <article class="map-app-news-item" id="news-waste-{{ $wasteNotice->id }}">
                <div class="map-app-news-meta">
                  仮置場の受入終了{{ $noticeDate ? ': ' . $noticeDate->format('Y/m/d') : '' }}
                </div>
                <p class="font-weight-bold">{{ $wasteNotice->name }}</p>
                @if ($wasteNotice->status_evidence)
                  <p>{{ \Illuminate\Support\Str::limit($wasteNotice->status_evidence, 200) }}</p>
                @endif
                @if ($noticeUrl)
                  <a href="{{ $noticeUrl }}" target="_blank" rel="noopener noreferrer">
                    {{ $noticeUrl }}
                  </a>
                @endif
              </article>
            @endforeach
            <hr>
          @endif
          <div class="text-muted small mb-2">
            最終収集日:
            {{ ($dataCollectionDates['information'] ?? null) ? $dataCollectionDates['information']->format('Y/m/d H:i') : '未収集' }}
          </div>
          @forelse ($disaster->informationLinks as $informationLink)
            <article class="map-app-news-item" id="news-link-{{ $informationLink->id }}">
              @if ($informationLink->fetched_at)
                <div class="map-app-news-meta">取得日時: {{ $informationLink->fetched_at->format('Y/m/d H:i') }}</div>
              @elseif ($informationLink->updated_at)
                <div class="map-app-news-meta">登録日時: {{ $informationLink->updated_at->format('Y/m/d H:i') }}</div>
              @endif
              <p>{{ $informationLink->description }}</p>
              @if ($informationLink->fetched_at && $informationLink->fetched_at->lt(now()->subMonth()))
                <div><span class="badge badge-warning">1か月以上経過</span></div>
              @endif
              <a href="{{ $informationLink->url }}" target="_blank" rel="noopener noreferrer">
                {{ $informationLink->url }}
              </a>
            </article>
          @empty
            <p class="text-muted mb-0">最新情報は登録されていません。</p>
          @endforelse
        </section>

        <section class="map-app-panel" data-map-panel-content="population" role="tabpanel" hidden>
          <div id="population-content">
            <p class="population-empty mb-0">人口データを読み込んでいます...</p>
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="support" role="tabpanel" hidden>
          <div class="support-panel">
            <div>
              <div class="support-section-header">
                <strong>道路規制</strong>
                <span id="support-road-count">読み込み中...</span>
              </div>
              <div class="text-muted small mb-2">
                最終収集日: {{ ($dataCollectionDates['roads'] ?? null) ? $dataCollectionDates['roads']->format('Y/m/d H:i') : '未収集' }}
              </div>
              <div id="support-road-list" class="support-list">
                <p class="support-empty">道路規制を読み込んでいます...</p>
              </div>
            </div>

            <div>
              <div class="support-section-header">
                <strong>災害廃棄物・仮置場</strong>
                <span id="support-waste-count">読み込み中...</span>
              </div>
              <div class="text-muted small mb-2">
                最終収集日: {{ ($dataCollectionDates['waste_sites'] ?? null) ? $dataCollectionDates['waste_sites']->format('Y/m/d H:i') : '未収集' }}
              </div>
              <div id="support-waste-list" class="support-list">
                <p class="support-empty">仮置場情報を読み込んでいます...</p>
              </div>
            </div>

            <div>
              <div class="support-section-header">
                <strong>議事録サマリ</strong>
                <span id="support-meeting-count">読み込み中...</span>
              </div>
              <div class="text-muted small mb-2">
                最終収集日: {{ ($dataCollectionDates['meeting_insights'] ?? null) ? $dataCollectionDates['meeting_insights']->format('Y/m/d H:i') : '未収集' }}
              </div>
              <div id="support-meeting-list" class="support-list">
                <p class="support-empty">議事録を読み込んでいます...</p>
              </div>
            </div>
          </div>
        </section>

        <section class="map-app-panel" data-map-panel-content="layers" role="tabpanel" hidden>
          <div class="map-layer-control">
            <div class="map-layer-title">表示レイヤー</div>

            <details open>
              <summary>行政界</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="municipalities" checked>
                現在の市町村境
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="small_areas">
                小区域界
              </label>
            </details>

            <details>
              <summary>避難所</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="shelters">
                指定避難所・指定緊急避難場所
              </label>
              <label class="map-layer-option" style="margin-left: 1.1rem;">
                <input type="checkbox" id="shelter-active-only">
                開設中のみ表示
              </label>
              <div class="map-layer-status">赤い波紋: 開設中 / 青: 指定避難所 / 橙: 指定緊急避難場所</div>
            </details>

            <details>
              <summary>ボランティア</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="volunteer_centers">
                ボランティアセンター
              </label>
            </details>

            <details>
              <summary>専門支援</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="roads">
                道路規制
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="waste_sites">
                災害廃棄物・仮置場
              </label>
              <div class="map-layer-status">赤: 全面通行止 / 橙: 片側交互 / 紫: 大型・重量規制 / 緑: 仮置場</div>
            </details>

            <details>
              <summary>学区</summary>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="elementary">
                小学校区
              </label>
              <label class="map-layer-option">
                <input type="checkbox" class="map-layer-toggle" data-layer="junior_high">
                中学校区
              </label>
            </details>

            <details open>
              <summary>合併前行政界</summary>
              @if ($historicalDates->isEmpty())
                <div class="map-layer-status">データ未取込</div>
              @else
                <label class="map-layer-option">
                  <input type="checkbox" class="map-layer-toggle" data-layer="historical">
                  表示
                </label>
                <label class="map-layer-option" style="margin-left: 0;">
                  基準日
                  <select id="historical-date" class="form-control form-control-sm mt-1">
                    @foreach ($historicalDates as $historicalDate)
                      <option value="{{ $historicalDate->format('Y-m-d') }}">
                        {{ $historicalDate->format('Y-m-d') }}
                      </option>
                    @endforeach
                  </select>
                </label>
                <label class="map-layer-option" style="margin-left: 0;">
                  旧市町村を選択
                  <select id="historical-name" class="form-control form-control-sm mt-1" disabled>
                    <option value="">基準日を選択してください</option>
                  </select>
                </label>
              @endif
            </details>

            <div id="layer-status" class="map-layer-status"></div>
            <div id="disaster-map-status" class="map-layer-status"></div>
          </div>

          <p class="map-layer-note">
            選択された市町村の行政区域を表示しています。学区は参考情報であり、公式な通学区域を示すものではありません。合併前行政界は基準日時点の参考情報で、区域にカーソルを合わせると旧市町村名を表示します。
          </p>
        </section>

      </div>

      <div class="map-app-sidebar-resizer" id="sidebar-resizer" role="separator" aria-orientation="vertical" aria-label="サイドメニューの幅を変更" tabindex="0"></div>
    </aside>
  </div>

@endsection


@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/topojson-client@3/dist/topojson-client.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('map-sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const sidebarStorageKey = 'disaster-map-sidebar-open';
      let mobileSheetState = 'minimized';

      function setSidebarOpen(open, persist) {
        if (!sidebar) {
          return;
        }

        sidebar.classList.toggle('is-open', open);
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (typeof sidebar.toggleAttribute === 'function') {
          sidebar.toggleAttribute('inert', !open);
        }

        if (sidebarToggle) {
          sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (isMobileViewport()) {
          setSheetState(open ? 'half' : 'minimized');
        }

        if (persist) {
          try {
            window.localStorage.setItem(sidebarStorageKey, open ? '1' : '0');
          } catch (error) {
          }
        }
      }

      if (sidebar && sidebarToggle) {
        let sidebarOpen = window.matchMedia('(min-width: 992px)').matches;

        try {
          const storedSidebar = window.localStorage.getItem(sidebarStorageKey);

          if (storedSidebar === '1' || storedSidebar === '0') {
            sidebarOpen = storedSidebar === '1';
          }
        } catch (error) {
        }

        setSidebarOpen(sidebarOpen, false);

        sidebarToggle.addEventListener('click', function () {
          setSidebarOpen(!sidebar.classList.contains('is-open'), true);
        });

        if (sidebarClose) {
          sidebarClose.addEventListener('click', function () {
            setSidebarOpen(false, true);
          });
        }
      }

      document.querySelectorAll('[data-map-panel]').forEach(function (tab) {
        tab.addEventListener('click', function () {
          const panel = this.dataset.mapPanel;

          document.querySelectorAll('[data-map-panel]').forEach(function (item) {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
          });

          document.querySelectorAll('[data-map-panel-content]').forEach(function (content) {
            content.hidden = content.dataset.mapPanelContent !== panel;
          });

          if (panel === 'population') {
            loadPopulationData();
          } else if (panel === 'support') {
            loadSupportPanelData();
          }
        });
      });

      const sidebarResizer = document.getElementById('sidebar-resizer');
      const sidebarWidthStorageKey = 'disaster-map-sidebar-width';
      const minSidebarWidth = 280;

      function maxSidebarWidth() {
        return Math.max(minSidebarWidth, Math.round(window.innerWidth * 0.9));
      }

      function clampSidebarWidth(width) {
        return Math.max(minSidebarWidth, Math.min(Math.round(width), maxSidebarWidth()));
      }

      function saveSidebarWidth() {
        if (!sidebar) {
          return;
        }

        try {
          window.localStorage.setItem(
            sidebarWidthStorageKey,
            String(Math.round(sidebar.getBoundingClientRect().width))
          );
        } catch (error) {
        }
      }

      if (sidebar && sidebarResizer) {
        try {
          const storedSidebarWidth = Number(window.localStorage.getItem(sidebarWidthStorageKey));

          if (storedSidebarWidth > 0) {
            sidebar.style.width = clampSidebarWidth(storedSidebarWidth) + 'px';
          }
        } catch (error) {
        }

        let resizingSidebar = false;
        let resizeStartX = 0;
        let resizeStartWidth = 0;

        const moveSidebarResize = function (event) {
          if (!resizingSidebar) {
            return;
          }

          sidebar.style.width = clampSidebarWidth(
            resizeStartWidth + event.clientX - resizeStartX
          ) + 'px';
        };

        const finishSidebarResize = function () {
          if (!resizingSidebar) {
            return;
          }

          resizingSidebar = false;
          sidebarResizer.classList.remove('is-resizing');
          document.body.classList.remove('map-app-resizing');
          window.removeEventListener('pointermove', moveSidebarResize);
          window.removeEventListener('pointerup', finishSidebarResize);
          saveSidebarWidth();
        };

        sidebarResizer.addEventListener('pointerdown', function (event) {
          if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
          }

          resizingSidebar = true;
          resizeStartX = event.clientX;
          resizeStartWidth = sidebar.getBoundingClientRect().width;
          sidebarResizer.classList.add('is-resizing');
          document.body.classList.add('map-app-resizing');
          event.preventDefault();
        });

        window.addEventListener('pointermove', moveSidebarResize);
        window.addEventListener('pointerup', finishSidebarResize);

        window.addEventListener('resize', function () {
          if (sidebar.style.width) {
            sidebar.style.width = clampSidebarWidth(
              sidebar.getBoundingClientRect().width
            ) + 'px';
          }
        });

        sidebarResizer.addEventListener('keydown', function (event) {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
          }

          const delta = event.key === 'ArrowRight' ? 20 : -20;
          sidebar.style.width = clampSidebarWidth(
            sidebar.getBoundingClientRect().width + delta
          ) + 'px';
          saveSidebarWidth();
          event.preventDefault();
        });
      }

      const municipalities = @json($mapMunicipalities);
      const municipalityBoundaries = new Map();
      const municipalityByCode = new Map(municipalities.map(function (municipality) {
        return [String(municipality.code), municipality];
      }));
      const schoolDistrictUrlTemplate = @json($schoolDistrictUrlTemplate);
      const historicalUrlTemplate = @json($historicalUrlTemplate);
      const populationUrl = @json(route('disasters.population', ['disaster' => $disaster->id]));
      const smallAreaUrl = @json(route('disasters.small_areas', ['disaster' => $disaster->id]));
      const evacuationShelterUrl = @json(route('disasters.evacuation_shelters', ['disaster' => $disaster->id]));
      const volunteerCenterUrl = @json(route('disasters.volunteer_centers', ['disaster' => $disaster->id]));
      const roadRegulationUrl = @json(url('/api/v1/disaster/roads'));
      const wasteSiteUrl = @json(url('/api/v1/disaster/waste-sites'));
      const meetingSummaryUrl = @json(url('/api/v1/disaster/meetings'));
      const mapElement = document.getElementById('disaster-map');

      if (!mapElement) {
        return;
      }

      const map = L.map(mapElement, {
        scrollWheelZoom: true,
        zoomControl: false,
      });

      L.control.zoom({ position: 'topright' }).addTo(map);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      map.attributionControl.addAttribution(
        '行政区域: <a href="https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-N03-v3_1.html" target="_blank" rel="noopener">国土数値情報（国土交通省）</a>'
      );
      map.attributionControl.addAttribution(
        '学区: <a href="https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-A27-2023.html" target="_blank" rel="noopener">国土数値情報 A27/A32</a>'
      );
      map.attributionControl.addAttribution(
        '合併前行政界: <a href="https://geoshape.ex.nii.ac.jp/city/" target="_blank" rel="noopener">歴史的行政区域データセットβ版（CODH）</a>'
      );
      map.attributionControl.addAttribution(
        '小区域: <a href="https://www.e-stat.go.jp/gis/statmap-search?page=1&type=2&aggregateUnitForBoundary=A&toukeiCode=00200521" target="_blank" rel="noopener">令和2年国勢調査 町丁・字等境界データ</a>'
      );
      map.attributionControl.addAttribution(
        '避難所: <a href="https://hinanmap.gsi.go.jp/" target="_blank" rel="noopener">国土地理院 指定緊急避難場所・指定避難所データ</a>'
      );
      map.attributionControl.addAttribution(
        'ボランティア: <a href="https://www.saigaivc.com/" target="_blank" rel="noopener">全国社会福祉協議会 被災者支援・ボランティア情報</a>'
      );
      map.attributionControl.addAttribution(
        '道路規制・仮置場: 千葉県防災ポータル、自治体公開情報等'
      );

      const layers = {
        municipalities: L.featureGroup().addTo(map),
        small_areas: L.featureGroup(),
        shelters: L.featureGroup(),
        volunteer_centers: L.featureGroup(),
        roads: L.featureGroup(),
        waste_sites: L.featureGroup(),
        elementary: L.featureGroup(),
        junior_high: L.featureGroup(),
        historical: L.featureGroup(),
      };

      const layerStyles = {
        municipalities: {
          color: '#1f3c88',
          weight: 2,
          fillColor: '#1f3c88',
          fillOpacity: 0.08
        },
        small_areas: {
          color: '#6c757d',
          weight: 1,
          opacity: 0.85,
          fillColor: '#adb5bd',
          fillOpacity: 0.02
        },
        elementary: {
          color: '#fd7e14',
          weight: 1.5,
          fillColor: '#fd7e14',
          fillOpacity: 0.18
        },
        junior_high: {
          color: '#6f42c1',
          weight: 1.5,
          fillColor: '#6f42c1',
          fillOpacity: 0.18
        },
        historical: {
          color: '#495057',
          weight: 1,
          fill: true,
          fillColor: '#adb5bd',
          fillOpacity: 0.08,
          dashArray: '4,2'
        }
      };

      const layerLoads = {};
      let shelterMarkers = [];
      let shelterLayerLoaded = false;
      let historicalFeatures = [];
      let historicalRequestId = 0;
      let selectedBoundaryLayer = null;
      const layerStorageKey = 'disaster-map-layer-visibility';
      const shelterActiveOnlyStorageKey = 'disaster-map-shelter-active-only';
      let mapViewInitialized = false;
      let viewLocked = false;

      function loadLayerVisibility() {
        try {
          const stored = window.localStorage.getItem(layerStorageKey);

          if (!stored) {
            return {};
          }

          const parsed = JSON.parse(stored);

          return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
          return {};
        }
      }

      function saveLayerVisibility() {
        const visibility = {};

        document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
          visibility[toggle.dataset.layer] = toggle.checked;
        });

        try {
          window.localStorage.setItem(layerStorageKey, JSON.stringify(visibility));
        } catch (error) {
        }
      }

      function setLayerStatus(message) {
        const status = document.getElementById('layer-status');
        if (status) {
          status.textContent = message || '';
        }
      }

      function resetHistoricalNameOptions(message) {
        historicalFeatures = [];
        clearBoundaryHighlight();

        const select = document.getElementById('historical-name');

        if (!select) {
          return;
        }

        select.innerHTML = '';

        const option = document.createElement('option');
        option.value = '';
        option.textContent = message;
        select.appendChild(option);
        select.disabled = true;
      }

      function populateHistoricalNameOptions() {
        const select = document.getElementById('historical-name');

        if (!select) {
          return;
        }

        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '旧市町村を選択してください';
        select.appendChild(placeholder);

        const names = new Set();

        historicalFeatures.forEach(function (item, index) {
          if (!item.name || names.has(item.name)) {
            return;
          }

          names.add(item.name);

          const option = document.createElement('option');
          option.value = String(index);
          option.textContent = item.name;
          select.appendChild(option);
        });

        select.disabled = historicalFeatures.length === 0;
      }

      function fitMapToVisibleLayers() {
        if (viewLocked) {
          return;
        }

        const bounds = L.latLngBounds([]);

        Object.keys(layers).forEach(function (name) {
          if (map.hasLayer(layers[name]) && layers[name].getLayers().length > 0) {
            bounds.extend(layers[name].getBounds());
          }
        });

        if (bounds.isValid()) {
          map.fitBounds(bounds, {
            padding: [20, 20],
            maxZoom: 12
          });
          mapViewInitialized = true;
        }
      }

      function ensureMapView() {
        if (!mapViewInitialized) {
          map.setView([36.2048, 138.2529], 5);
          mapViewInitialized = true;
        }
      }

      function layerStyle(name) {
        return layerStyles[name] || layerStyles.municipalities;
      }

      function boundaryBaseStyle(layer) {
        return {
          color: layer.options.color,
          weight: layer.options.weight,
          opacity: layer.options.opacity,
          fill: layer.options.fill,
          fillColor: layer.options.fillColor,
          fillOpacity: layer.options.fillOpacity,
          dashArray: layer.options.dashArray
        };
      }

      function clearBoundaryHighlight() {
        if (selectedBoundaryLayer && selectedBoundaryLayer.setStyle) {
          selectedBoundaryLayer.setStyle(
            selectedBoundaryLayer.saizBaseStyle || layerStyle('historical')
          );
        }

        selectedBoundaryLayer = null;
      }

      function highlightBoundary(layer) {
        if (!layer || !layer.setStyle) {
          return;
        }

        if (selectedBoundaryLayer !== layer) {
          clearBoundaryHighlight();
        }

        if (!layer.saizBaseStyle) {
          layer.saizBaseStyle = boundaryBaseStyle(layer);
        }

        layer.setStyle({
          color: '#dc3545',
          weight: 4,
          opacity: 1,
          fill: false,
          fillOpacity: 0,
          dashArray: null
        });

        if (layer.bringToFront) {
          layer.bringToFront();
        }

        selectedBoundaryLayer = layer;
      }

      let populationLoaded = false;

      function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function formatPopulationNumber(value) {
        return Number(value || 0).toLocaleString('ja-JP');
      }

      function populationPercent(part, total) {
        if (!total) {
          return '0.0';
        }

        return ((Number(part || 0) / Number(total)) * 100).toFixed(1);
      }

      function populationSourceLink(source) {
        if (!source) {
          return '';
        }

        const name = escapeHtml(source.name || '出典');
        const url = escapeHtml(source.url || '');

        if (!url) {
          return name;
        }

        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + name + '</a>';
      }

      function renderPopulationMunicipality(data) {
        const municipality = data.municipality || {};
        const summary = data.summary || {};
        const foreigners = summary.foreigners || {};
        const ageGroups = data.age_groups || {};
        const groups = ageGroups.groups || [];
        const smallAreas = (data.small_areas && data.small_areas.items) || [];
        const population = Number(summary.population || 0);
        const foreignerTotal = Number(foreigners.total || 0);
        const age65 = groups.reduce(function (total, group) {
          return total + (parseInt(group.label, 10) >= 65 ? Number(group.total || 0) : 0);
        }, 0);
        const maxAgeTotal = Math.max.apply(null, groups.map(function (group) {
          return Number(group.total || 0);
        }).concat([1]));

        const ageRows = groups.map(function (group) {
          const total = Number(group.total || 0);
          const foreignerCount = Number(group.foreigners || 0);
          const width = Math.max(0, Math.min(100, (total / maxAgeTotal) * 100));
          const foreignerRow = foreignerCount > 0
            ? '<span class="population-age-foreigners">外国人 ' + formatPopulationNumber(foreignerCount) + '人</span>'
            : '';

          return '<div class="population-age-row">'
            + '<span class="population-age-label">' + escapeHtml(group.label) + '</span>'
            + '<div class="population-age-bar"><span style="width: ' + width.toFixed(1) + '%"></span></div>'
            + '<span class="population-age-total">' + formatPopulationNumber(total) + '</span>'
            + foreignerRow
            + '</div>';
        }).join('');

        const smallAreaRows = smallAreas.map(function (area) {
          return '<tr>'
            + '<td>' + escapeHtml(area.name) + '</td>'
            + '<td class="number">' + formatPopulationNumber(area.population) + '</td>'
            + '<td class="number">' + formatPopulationNumber(area.age_65_plus) + '</td>'
            + '<td class="number">' + populationPercent(area.age_65_plus, area.population) + '%</td>'
            + '</tr>';
        }).join('');

        return '<details class="population-municipality" open>'
          + '<summary class="population-header">'
          + '<span class="population-title">' + escapeHtml(municipality.name) + '</span>'
          + '<span class="population-date">' + escapeHtml(summary.as_of || '') + '現在</span>'
          + '</summary>'
          + '<div class="population-stats">'
          + '<div class="population-stat"><span class="population-stat-label">総人口</span><span class="population-stat-value">' + formatPopulationNumber(population) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">世帯数</span><span class="population-stat-value">' + formatPopulationNumber(summary.households) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">外国人</span><span class="population-stat-value">' + formatPopulationNumber(foreignerTotal) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">外国人比率</span><span class="population-stat-value">' + populationPercent(foreignerTotal, population) + '%</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">65歳以上（2020年）</span><span class="population-stat-value">' + formatPopulationNumber(age65) + '</span></div>'
          + '<div class="population-stat"><span class="population-stat-label">高齢化率（2020年）</span><span class="population-stat-value">' + populationPercent(age65, groups.reduce(function (total, group) { return total + Number(group.total || 0); }, 0)) + '%</span></div>'
          + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(summary.source) + '</p>'
          + '<h3 class="population-section-title">年齢階層 <small>' + escapeHtml(ageGroups.as_of || '') + ' / 国勢調査</small></h3>'
          + '<div class="population-age-list">' + ageRows + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(ageGroups.source) + '</p>'
          + '<details class="population-small-areas">'
          + '<summary>小区域（町丁・字等） ' + smallAreas.length + '件</summary>'
          + '<div class="population-table-wrap">'
          + '<table class="population-table"><thead><tr><th>地区</th><th>人口</th><th>65歳以上</th><th>高齢化率</th></tr></thead>'
          + '<tbody>' + smallAreaRows + '</tbody></table>'
          + '</div>'
          + '<p class="population-source">出典: ' + populationSourceLink(data.small_areas && data.small_areas.source) + '</p>'
          + '</details>'
          + '<p class="population-mesh-note">メッシュデータは未取込です。</p>'
          + '</details>';
      }

      function renderPopulation(data) {
        const container = document.getElementById('population-content');

        if (!container) {
          return;
        }

        const municipalitiesData = data.municipalities || [];

        if (municipalitiesData.length === 0) {
          const missing = (data.meta && data.meta.missing) || [];
          container.innerHTML = '<p class="population-empty mb-0">人口データが登録されていません。'
            + (missing.length > 0 ? '<br>未取込: ' + escapeHtml(missing.join('、')) : '')
            + '</p>';
          return;
        }

        container.innerHTML = municipalitiesData.map(renderPopulationMunicipality).join('');
      }

      function loadPopulationData() {
        if (populationLoaded) {
          return;
        }

        populationLoaded = true;

        fetch(populationUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(renderPopulation)
          .catch(function () {
            populationLoaded = false;

            const container = document.getElementById('population-content');

            if (container) {
              container.innerHTML = '<p class="population-empty mb-0">人口データを取得できませんでした。</p>';
            }
          });
      }

      function loadMunicipalitiesLayer() {
        const requests = [];

        municipalities.forEach(function (municipality) {
          municipality.codes.forEach(function (code) {
            const url = 'https://geolonia.github.io/japanese-admins/'
              + municipality.prefecture_code + '/' + code + '.json';

            requests.push(
              fetch(url)
                .then(function (response) {
                  if (!response.ok) {
                    throw new Error(response.status);
                  }
                  return response.json();
                })
                .then(function (geojson) {
                  const boundaryGroup = L.geoJSON(geojson, {
                    style: layerStyle('municipalities'),
                    onEachFeature: function (feature, layer) {
                      layer.saizMunicipalityCode = String(municipality.code);
                      layer.saizBaseStyle = layerStyle('municipalities');

                      if (!municipalityBoundaries.has(String(municipality.code))) {
                        municipalityBoundaries.set(String(municipality.code), []);
                      }

                      municipalityBoundaries.get(String(municipality.code)).push(layer);

                      layer.on('click', function () {
                        selectMunicipality(String(municipality.code), true);
                      });
                      layer.on('mouseover', function () {
                        setMunicipalityHover(String(municipality.code));
                      });
                      layer.on('mouseout', function () {
                        clearMunicipalityHover(String(municipality.code));
                      });
                    }
                  });

                  boundaryGroup.addTo(layers.municipalities);
                })
                .catch(function () {
                  // 取得できない区域はスキップする
                })
            );
          });
        });

        return Promise.all(requests).then(function () {
          if (layers.municipalities.getLayers().length > 0) {
            fitMapToVisibleLayers();
          } else {
            const status = document.getElementById('disaster-map-status');
            if (status) {
              status.textContent = '行政区域データを取得できませんでした。時間をおいて再度お試しください。';
            }
          }

          ensureMapView();

          window.setTimeout(function () {
            map.invalidateSize();
          }, 100);
        });
      }

      function navigationUrl(latitude, longitude) {
        return 'https://www.google.com/maps/dir/?api=1&destination='
          + encodeURIComponent(latitude + ',' + longitude)
          + '&travelmode=driving&dir_action=navigate';
      }

      function appendNavigationButton(popup, latitude, longitude) {
        const link = document.createElement('a');
        link.className = 'map-popup-navigation';
        link.href = navigationUrl(latitude, longitude);
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = 'ここに行く';
        link.setAttribute('aria-label', 'Googleマップでここへの経路を開く');
        popup.appendChild(link);
      }

      function shelterIcon(type, isActive) {
        let className = type === 'emergency_site'
          ? 'shelter-marker shelter-marker-emergency'
          : 'shelter-marker';

        if (isActive) {
          className = 'shelter-marker shelter-marker-active';
        }

        return L.divIcon({
          className: className,
          html: '<span></span>',
          iconSize: [18, 18],
          iconAnchor: [9, 9],
          popupAnchor: [0, -10]
        });
      }

      function shelterActiveOnlyEnabled() {
        const toggle = document.getElementById('shelter-active-only');
        return Boolean(toggle && toggle.checked);
      }

      function applyShelterFilter() {
        const activeOnly = shelterActiveOnlyEnabled();
        const total = shelterMarkers.length;
        const activeTotal = shelterMarkers.filter(function (entry) {
          return entry.active;
        }).length;

        shelterMarkers.forEach(function (entry) {
          if (!activeOnly || entry.active) {
            if (!layers.shelters.hasLayer(entry.marker)) {
              entry.marker.addTo(layers.shelters);
            }
          } else if (layers.shelters.hasLayer(entry.marker)) {
            layers.shelters.removeLayer(entry.marker);
          }
        });

        const visible = layers.shelters.getLayers().length;

        if (activeOnly) {
          setLayerStatus(visible > 0
            ? '開設中の避難所を表示中（' + visible + '件 / 全' + total + '件）'
            : '開設中の避難所はありません（全' + total + '件）');
        } else {
          setLayerStatus('避難所を表示中（' + total + '件 / 開設中 ' + activeTotal + '件）');
        }

        if (map.hasLayer(layers.shelters) && visible > 0) {
          fitMapToVisibleLayers();
        }
      }

      function loadShelterLayer() {
        if (shelterLayerLoaded) {
          return Promise.resolve();
        }

        if (layerLoads.shelters) {
          return layerLoads.shelters;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="shelters"]');

        layerLoads.shelters = fetch(evacuationShelterUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            const shelters = data.shelters || [];

            if (shelters.length === 0) {
              shelterLayerLoaded = true;
              setLayerStatus('避難所データは未登録です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.shelters);
              return;
            }

            shelterMarkers = [];

            shelters.forEach(function (shelter) {
              const latitude = Number(shelter.latitude);
              const longitude = Number(shelter.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.marker([latitude, longitude], {
                icon: shelterIcon(shelter.shelter_type, shelter.is_active),
                zIndexOffset: shelter.is_active ? 1000 : 0
              });
              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = shelter.name;
              popup.appendChild(title);

              if (shelter.is_active) {
                const active = document.createElement('div');
                active.style.color = '#dc3545';
                active.style.fontWeight = 'bold';
                active.textContent = '開設中';
                popup.appendChild(active);
              }

              const type = document.createElement('div');
              type.textContent = shelter.shelter_type === 'emergency_site'
                ? '指定緊急避難場所'
                : '指定避難所';
              popup.appendChild(type);

              if (shelter.address) {
                const address = document.createElement('div');
                address.textContent = shelter.address;
                popup.appendChild(address);
              }

              appendNavigationButton(popup, latitude, longitude);

              if (Array.isArray(shelter.disaster_types) && shelter.disaster_types.length > 0) {
                const disasters = document.createElement('div');
                disasters.textContent = '対象災害: ' + shelter.disaster_types.join('、');
                popup.appendChild(disasters);
              }

              if (shelter.note) {
                const note = document.createElement('div');
                note.textContent = shelter.note;
                popup.appendChild(note);
              }

              if (shelter.is_active && shelter.evacuees !== null && shelter.evacuees !== undefined) {
                const evacuees = document.createElement('div');
                evacuees.textContent = '避難者数: ' + Number(shelter.evacuees).toLocaleString('ja-JP') + '人';
                popup.appendChild(evacuees);
              }

              if (shelter.is_active && shelter.status_note) {
                const statusNote = document.createElement('div');
                statusNote.textContent = shelter.status_note;
                popup.appendChild(statusNote);
              }

              if (shelter.status_checked_at) {
                const checked = document.createElement('div');
                checked.style.color = '#6c757d';
                checked.textContent = '開設状況確認: ' + new Date(shelter.status_checked_at).toLocaleString('ja-JP');
                popup.appendChild(checked);
              }

              marker.bindPopup(popup);
              shelterMarkers.push({
                marker: marker,
                active: Boolean(shelter.is_active)
              });
            });

            shelterLayerLoaded = true;
            applyShelterFilter();
          })
          .catch(function () {
            shelterLayerLoaded = false;
            shelterMarkers = [];
            setLayerStatus('避難所データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.shelters);
          })
          .finally(function () {
            layerLoads.shelters = null;
          });

        return layerLoads.shelters;
      }

      function volunteerCenterIcon() {
        return L.divIcon({
          className: 'volunteer-center-marker',
          html: '<span></span>',
          iconSize: [20, 20],
          iconAnchor: [10, 10],
          popupAnchor: [0, -12]
        });
      }

      function loadVolunteerCenterLayer() {
        if (layers.volunteer_centers.getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.volunteer_centers) {
          return layerLoads.volunteer_centers;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="volunteer_centers"]');

        layerLoads.volunteer_centers = fetch(volunteerCenterUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            const centers = data.centers || [];

            if (centers.length === 0) {
              setLayerStatus('ボランティアセンターは未登録です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.volunteer_centers);
              return;
            }

            centers.forEach(function (center) {
              if (center.activity_status === 'closed') {
                return;
              }

              const latitude = Number(center.latitude);
              const longitude = Number(center.longitude);

              if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
              }

              const marker = L.marker([latitude, longitude], {
                icon: volunteerCenterIcon()
              });
              const popup = document.createElement('div');
              const title = document.createElement('strong');
              title.textContent = center.name;
              popup.appendChild(title);

              const type = document.createElement('div');
              type.textContent = '災害ボランティアセンター';
              popup.appendChild(type);

              const activity = document.createElement('div');
              if (center.activity_status === 'open') {
                activity.textContent = '活動状況: 開設中';
                activity.style.color = '#28a745';
                activity.style.fontWeight = 'bold';
              } else {
                activity.textContent = '活動状況: 未確認';
                activity.style.color = '#6c757d';
              }
              popup.appendChild(activity);

              if (center.status_note) {
                const statusNote = document.createElement('div');
                statusNote.textContent = center.status_note;
                popup.appendChild(statusNote);
              }

              if (center.status_checked_at) {
                const statusChecked = document.createElement('div');
                statusChecked.style.color = '#6c757d';
                statusChecked.textContent = '状況確認: ' + new Date(center.status_checked_at).toLocaleString('ja-JP');
                popup.appendChild(statusChecked);
              }

              if (center.address) {
                const address = document.createElement('div');
                address.textContent = center.address;
                popup.appendChild(address);
              }

              if (center.phone) {
                const phone = document.createElement('div');
                phone.textContent = 'TEL: ' + center.phone;
                popup.appendChild(phone);
              }

              appendNavigationButton(popup, latitude, longitude);

              const recruitment = center.latest_recruitment || {};
              const recruitmentBox = document.createElement('div');
              recruitmentBox.style.marginTop = '0.5rem';

              const recruitmentLabel = document.createElement('strong');
              recruitmentLabel.textContent = '最新のボランティア募集';
              recruitmentBox.appendChild(recruitmentLabel);

              if (recruitment.title) {
                const recruitmentTitle = document.createElement('div');
                recruitmentTitle.textContent = recruitment.title;
                recruitmentBox.appendChild(recruitmentTitle);
              }

              if (recruitment.summary) {
                const recruitmentSummary = document.createElement('div');
                recruitmentSummary.textContent = recruitment.summary;
                recruitmentBox.appendChild(recruitmentSummary);
              }

              if (recruitment.url) {
                const recruitmentLink = document.createElement('a');
                recruitmentLink.href = recruitment.url;
                recruitmentLink.target = '_blank';
                recruitmentLink.rel = 'noopener noreferrer';
                recruitmentLink.textContent = '募集情報の詳細';
                recruitmentBox.appendChild(recruitmentLink);
              }

              if (recruitment.updated_at) {
                const updated = document.createElement('div');
                updated.style.color = '#6c757d';
                updated.textContent = '情報更新: ' + new Date(recruitment.updated_at).toLocaleString('ja-JP');
                recruitmentBox.appendChild(updated);
              }

              if (!recruitment.title && !recruitment.summary) {
                const empty = document.createElement('div');
                empty.textContent = '募集情報は未取得です。';
                recruitmentBox.appendChild(empty);
              }

              popup.appendChild(recruitmentBox);

              if (center.url) {
                const site = document.createElement('a');
                site.href = center.url;
                site.target = '_blank';
                site.rel = 'noopener noreferrer';
                site.textContent = '公式サイト';
                site.style.display = 'block';
                site.style.marginTop = '0.35rem';
                popup.appendChild(site);
              }

              marker.bindPopup(popup);
              marker.addTo(layers.volunteer_centers);
            });

            setLayerStatus('ボランティアセンターを表示中（' + layers.volunteer_centers.getLayers().length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus('ボランティアセンター情報を取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.volunteer_centers);
          })
          .finally(function () {
            layerLoads.volunteer_centers = null;
          });

        return layerLoads.volunteer_centers;
      }

      const supportRoadFeatures = [];
      const supportWasteFeatures = [];
      const supportMeetingItems = [];
      const supportRoadLayers = new Map();
      const supportWasteLayers = new Map();

      function roadTypeMeta(type, status) {
        if (status === 'open') {
          return { label: '解除済み', color: '#198754', guidance: '通行止めは解除されています。最新情報を確認してください。' };
        }

        return {
          full_closure: { label: '全面通行止', color: '#dc3545', guidance: 'この区間は通行できません。迂回路を確認してください。' },
          alternating: { label: '片側交互通行', color: '#fd7e14', guidance: '片側交互で通行できます。現地の誘導に従ってください。' },
          large_vehicle: { label: '大型車規制', color: '#6f42c1', guidance: '大型車は通行できません。車両制限を確認してください。' },
          weight_limit: { label: '重量規制', color: '#6f42c1', guidance: '重量制限があります。積載量を確認してください。' }
        }[type] || { label: 'その他規制', color: '#495057', guidance: '現地の規制表示を確認してください。' };
      }

      function vehicleLabels(vehicles) {
        const labels = {
          light_truck: '軽トラ',
          '2t': '2t車',
          '4t': '4t車',
          large: '大型回送車'
        };

        if (!Array.isArray(vehicles) || vehicles.length === 0) {
          return '車両制限なし・不明';
        }

        return vehicles.map(function (vehicle) {
          return labels[vehicle] || vehicle;
        }).join(' / ');
      }

      function sourceHtml(properties) {
        const source = properties.source || {};
        const url = String(source.url || '');

        return '<div class="support-item-meta">'
          + '出典: ' + escapeHtml(source.org || '不明')
          + (url.startsWith('http') ? '<br><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">一次資料を開く</a>' : '')
          + (source.published_at ? '<br>公表: ' + escapeHtml(new Date(source.published_at).toLocaleString('ja-JP')) : '')
          + (source.credibility ? '<br>出典信頼度: ' + escapeHtml(source.credibility) : '')
          + (source.credibility_reason ? '<br>判定理由: ' + escapeHtml(source.credibility_reason) : '')
          + '</div>';
      }

      function staleBadge(properties, source) {
        const value = source && source.published_at
          ? source.published_at
          : (properties.updated_at || properties.last_verified_at);

        if (!value) {
          return '';
        }

        const timestamp = new Date(value).getTime();

        if (Number.isNaN(timestamp)) {
          return '';
        }

        const threshold = new Date();
        threshold.setMonth(threshold.getMonth() - 1);

        return timestamp < threshold.getTime()
          ? '<span class="badge badge-warning ml-1">1か月以上経過</span>'
          : '';
      }

      function roadPopup(properties) {
        const meta = roadTypeMeta(properties.regulation_type, properties.status);
        const root = document.createElement('div');
        const stale = staleBadge(properties, properties.source);

        root.innerHTML = '<strong>' + escapeHtml(properties.road_name || '道路規制') + '</strong>'
          + '<div style="margin:0.3rem 0;"><span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span></div>'
          + '<div style="font-weight:600;margin-bottom:0.35rem;">' + escapeHtml(meta.guidance) + '</div>'
          + '<div><strong>区間:</strong> ' + escapeHtml(properties.section || '不明') + '</div>'
          + '<div><strong>対象車両:</strong> ' + escapeHtml(vehicleLabels(properties.vehicle_classes)) + '</div>'
          + '<div><strong>事由:</strong> ' + escapeHtml(properties.reason || '不明') + '</div>'
          + '<div class="support-item-meta">信頼度: ' + escapeHtml(properties.confidence_rank || 'C') + '</div>'
          + (stale ? '<div>' + stale + '</div>' : '')
          + sourceHtml(properties)
          + '<div class="support-item-meta" style="color:#b02a37;margin-top:0.35rem;">' + escapeHtml(properties.disclaimer || '出典の一次情報を確認してください。') + '</div>';

        return root;
      }

      function wasteStatusMeta(status) {
        return {
          open: { label: '開設中', color: '#198754' },
          closed: { label: '閉鎖', color: '#6c757d' }
        }[status] || { label: '不明', color: '#0d6efd' };
      }

      function supportConditionsHtml(conditions) {
        const labels = {
          contact: '連絡先',
          other_contact: 'その他連絡先',
          fee_free: '料金',
          id_verification: '本人確認',
          permit_required: '許可申請',
          reception_hours: '受付時間',
          application_form: '申請書',
          permit_form: '申請書',
          sorting_requested: '分別',
          business_waste_accepted: '事業系ごみ',
          direct_dropoff_by_citizen: '市民直接搬入',
          notes: '注意事項',
          unloading: '搬入方法',
          closure_conditions: '閉鎖条件',
          required_documents: '必要書類',
          advance_reservation_required: '事前予約',
          advance_consultation_required_for_large_loads: '多量搬入の事前相談'
        };
        const booleans = {
          fee_free: ['無料', '有料'],
          id_verification: ['必要', '不要'],
          permit_required: ['必要', '不要'],
          sorting_requested: ['必要', '不要'],
          business_waste_accepted: ['可', '不可'],
          direct_dropoff_by_citizen: ['可', '不可'],
          advance_reservation_required: ['必要', '不要'],
          advance_consultation_required_for_large_loads: ['必要', '不要']
        };
        const entries = Object.entries(conditions || {}).filter(function (entry) {
          return entry[1] !== null && entry[1] !== undefined && entry[1] !== '';
        });

        if (!entries.length) {
          return '<div class="support-item-meta">不明</div>';
        }

        return entries.map(function (entry) {
          const key = entry[0];
          const value = entry[1];
          let text;

          if (typeof value === 'boolean') {
            const pair = booleans[key] || ['あり', 'なし'];
            text = value ? pair[0] : pair[1];
          } else if (Array.isArray(value)) {
            text = value.join('、');
          } else if (typeof value === 'object') {
            text = JSON.stringify(value);
          } else {
            text = String(value);
          }

          return '<div><strong>' + escapeHtml(labels[key] || key) + ':</strong> ' + escapeHtml(text) + '</div>';
        }).join('');
      }

      function wastePopup(properties) {
        const meta = wasteStatusMeta(properties.status);
        const root = document.createElement('div');

        root.innerHTML = '<strong>' + escapeHtml(properties.name || '仮置場') + '</strong>'
          + '<div style="margin:0.3rem 0;"><span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span></div>'
          + '<div><strong>受入品目:</strong> ' + escapeHtml((properties.accepted_items || []).join('、') || '不明') + '</div>'
          + '<div><strong>受入不可:</strong> ' + escapeHtml((properties.rejected_items || []).join('、') || '不明') + '</div>'
          + '<div><strong>車両制限:</strong> ' + escapeHtml(properties.vehicle_limit || (properties.max_payload_tons ? properties.max_payload_tons + 'tまで' : '不明')) + '</div>'
          + '<div><strong>搬入条件:</strong></div>' + supportConditionsHtml(properties.support_conditions || {})
          + sourceHtml(properties)
          + '<div class="support-item-meta" style="color:#b02a37;margin-top:0.35rem;">' + escapeHtml(properties.disclaimer || '出典の一次情報を確認してください。') + '</div>';

        return root;
      }

      function focusSupportFeature(layerName, id, layersById) {
        const layer = layersById.get(id);
        const toggle = document.querySelector('.map-layer-toggle[data-layer="' + layerName + '"]');

        if (toggle && !toggle.checked) {
          toggle.checked = true;
          map.addLayer(layers[layerName]);
          saveLayerVisibility();
        }

        if (!layer) {
          return;
        }

        if (layer.getBounds && layer.getBounds().isValid()) {
          map.fitBounds(layer.getBounds(), { padding: [40, 40], maxZoom: 15 });
        } else if (layer.getLatLng) {
          map.setView(layer.getLatLng(), 15);
        }

        if (layer.openPopup) {
          layer.openPopup();
        }
      }

      function renderSupportRoadList() {
        const list = document.getElementById('support-road-list');
        const count = document.getElementById('support-road-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportRoadFeatures.length + '件';
        }

        if (supportRoadFeatures.length === 0) {
          list.innerHTML = '<p class="support-empty">道路規制は登録されていません。</p>';
          return;
        }

        list.innerHTML = '';
        supportRoadFeatures.forEach(function (feature) {
          const properties = feature.properties || {};
          const meta = roadTypeMeta(properties.regulation_type, properties.status);
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'support-item';
          button.innerHTML = '<span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span>'
            + '<span class="support-item-name">' + escapeHtml(properties.road_name || '道路名不明') + '</span>'
            + '<span class="support-item-meta">' + escapeHtml(properties.section || '区間不明') + '</span>'
            + '<span class="support-item-meta">対象: ' + escapeHtml(vehicleLabels(properties.vehicle_classes)) + '</span>'
            + staleBadge(properties, properties.source);
          button.addEventListener('click', function () {
            focusSupportFeature('roads', properties.id, supportRoadLayers);
          });
          list.appendChild(button);
        });
      }

      function renderSupportWasteList() {
        const list = document.getElementById('support-waste-list');
        const count = document.getElementById('support-waste-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportWasteFeatures.length + '件';
        }

        if (supportWasteFeatures.length === 0) {
          list.innerHTML = '<p class="support-empty">仮置場は登録されていません。</p>';
          return;
        }

        list.innerHTML = '';
        supportWasteFeatures.forEach(function (feature) {
          const properties = feature.properties || {};
          const meta = wasteStatusMeta(properties.status);
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'support-item';
          button.innerHTML = '<span class="support-badge" style="background:' + meta.color + '">' + escapeHtml(meta.label) + '</span>'
            + '<span class="support-item-name">' + escapeHtml(properties.name || '仮置場名不明') + '</span>'
            + '<span class="support-item-meta">受入: ' + escapeHtml((properties.accepted_items || []).join('、') || '不明') + '</span>'
            + '<span class="support-item-meta">車両: ' + escapeHtml(properties.vehicle_limit || (properties.max_payload_tons ? properties.max_payload_tons + 'tまで' : '不明')) + '</span>'
            + staleBadge(properties, properties.source);
          button.addEventListener('click', function () {
            focusSupportFeature('waste_sites', properties.id, supportWasteLayers);
          });
          list.appendChild(button);
        });
      }

      function meetingItemsHtml(items) {
        if (!Array.isArray(items) || items.length === 0) {
          return '';
        }

        return '<ul>' + items.map(function (item) {
          return '<li>' + escapeHtml(String(item)) + '</li>';
        }).join('') + '</ul>';
      }

      function meetingBlock(label, items) {
        const html = meetingItemsHtml(items);

        if (!html) {
          return '';
        }

        return '<div class="support-meeting-block"><strong>' + escapeHtml(label) + '</strong>' + html + '</div>';
      }

      function renderSupportMeetingList() {
        const list = document.getElementById('support-meeting-list');
        const count = document.getElementById('support-meeting-count');

        if (!list) {
          return;
        }

        if (count) {
          count.textContent = supportMeetingItems.length + '件';
        }

        if (supportMeetingItems.length === 0) {
          list.innerHTML = '<p class="support-empty">議事録は登録されていません。</p>';
          return;
        }

        list.innerHTML = supportMeetingItems.map(function (meeting) {
          const source = meeting.source || {};
          const heldAt = meeting.held_at ? new Date(meeting.held_at).toLocaleString('ja-JP') : '日時不明';
          const stale = staleBadge(meeting, source);
          const blocks = meetingBlock('決定事項', meeting.decisions)
            + meetingBlock('方針転換・撤収予定', meeting.policy_changes)
            + meetingBlock('未充足ニーズ・支援の空白', meeting.unmet_needs);

          return '<article class="support-meeting">'
            + '<span class="support-meeting-title">' + escapeHtml(meeting.meeting_name || '会議名不明') + '</span>'
            + '<span class="support-item-meta">' + escapeHtml(heldAt) + (source.org ? ' / ' + escapeHtml(source.org) : '') + '</span>'
            + stale
            + (blocks || '<div class="support-item-meta">抽出された決定事項・ニーズはありません。</div>')
            + '<div class="support-meeting-source">出典: ' + escapeHtml(source.org || '不明')
            + (source.url ? '<br><a href="' + escapeHtml(source.url) + '" target="_blank" rel="noopener noreferrer">一次資料を開く</a>' : '')
            + (source.credibility ? '<br>出典信頼度: ' + escapeHtml(source.credibility) : '')
            + (source.credibility_reason ? '<br>判定理由: ' + escapeHtml(source.credibility_reason) : '')
            + '</div>'
            + '</article>';
        }).join('');
      }

      function updateSupportTabCount() {
        const badge = document.getElementById('support-tab-count');

        if (!badge) {
          return;
        }

        const total = supportRoadFeatures.length + supportWasteFeatures.length + supportMeetingItems.length;
        badge.textContent = String(total);
        badge.hidden = total === 0;
      }

      function loadRoadLayer() {
        if (supportRoadFeatures.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.roads) {
          return layerLoads.roads;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="roads"]');

        layerLoads.roads = fetch(roadRegulationUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportRoadFeatures.length = 0;
            supportRoadLayers.clear();

            (data.features || []).forEach(function (feature) {
              supportRoadFeatures.push(feature);
            });

            const geoLayer = L.geoJSON(data, {
              style: function (feature) {
                const meta = roadTypeMeta(feature.properties.regulation_type, feature.properties.status);
                return { color: meta.color, weight: 5, opacity: 0.95, lineCap: 'round' };
              },
              pointToLayer: function (feature, latlng) {
                const meta = roadTypeMeta(feature.properties.regulation_type, feature.properties.status);
                return L.circleMarker(latlng, {
                  radius: 8,
                  color: '#fff',
                  weight: 2,
                  fillColor: meta.color,
                  fillOpacity: 1
                });
              },
              onEachFeature: function (feature, layer) {
                const id = feature.properties.id;
                supportRoadLayers.set(id, layer);
                layer.bindPopup(roadPopup(feature.properties));
              }
            });

            layers.roads.clearLayers();
            geoLayer.addTo(layers.roads);
            renderSupportRoadList();
            updateSupportTabCount();
            setLayerStatus('道路規制を表示中（' + supportRoadFeatures.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            supportRoadFeatures.length = 0;
            supportRoadLayers.clear();
            renderSupportRoadList();
            updateSupportTabCount();
            setLayerStatus('道路規制データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.roads);
          })
          .finally(function () {
            layerLoads.roads = null;
          });

        return layerLoads.roads;
      }

      function loadWasteSiteLayer() {
        if (supportWasteFeatures.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.waste_sites) {
          return layerLoads.waste_sites;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="waste_sites"]');

        layerLoads.waste_sites = fetch(wasteSiteUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportWasteFeatures.length = 0;
            supportWasteLayers.clear();

            (data.features || []).forEach(function (feature) {
              supportWasteFeatures.push(feature);
            });

            const geoLayer = L.geoJSON(data, {
              pointToLayer: function (feature, latlng) {
                const meta = wasteStatusMeta(feature.properties.status);
                return L.circleMarker(latlng, {
                  radius: 9,
                  color: '#fff',
                  weight: 2,
                  fillColor: meta.color,
                  fillOpacity: 1
                });
              },
              onEachFeature: function (feature, layer) {
                const id = feature.properties.id;
                supportWasteLayers.set(id, layer);
                layer.bindPopup(wastePopup(feature.properties));
              }
            });

            layers.waste_sites.clearLayers();
            geoLayer.addTo(layers.waste_sites);
            renderSupportWasteList();
            updateSupportTabCount();
            setLayerStatus('仮置場を表示中（' + supportWasteFeatures.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            supportWasteFeatures.length = 0;
            supportWasteLayers.clear();
            renderSupportWasteList();
            updateSupportTabCount();
            setLayerStatus('仮置場データを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.waste_sites);
          })
          .finally(function () {
            layerLoads.waste_sites = null;
          });

        return layerLoads.waste_sites;
      }

      function loadMeetingSummaries() {
        if (supportMeetingItems.length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.meetings) {
          return layerLoads.meetings;
        }

        const list = document.getElementById('support-meeting-list');

        layerLoads.meetings = fetch(meetingSummaryUrl + '?disaster_id=' + encodeURIComponent(@json($disaster->id)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            supportMeetingItems.length = 0;
            (data.meetings || []).forEach(function (meeting) {
              supportMeetingItems.push(meeting);
            });
            renderSupportMeetingList();
            updateSupportTabCount();
          })
          .catch(function () {
            if (list) {
              list.innerHTML = '<p class="support-empty">議事録を取得できませんでした。</p>';
            }
            const count = document.getElementById('support-meeting-count');
            if (count) {
              count.textContent = '取得失敗';
            }
          })
          .finally(function () {
            layerLoads.meetings = null;
          });

        return layerLoads.meetings;
      }

      function loadSupportPanelData() {
        loadRoadLayer();
        loadWasteSiteLayer();
        loadMeetingSummaries();
      }

      function loadSmallAreaLayer() {
        if (layers.small_areas.getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads.small_areas) {
          return layerLoads.small_areas;
        }

        const toggle = document.querySelector('.map-layer-toggle[data-layer="small_areas"]');

        layerLoads.small_areas = fetch(smallAreaUrl, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (!data.features || data.features.length === 0) {
              setLayerStatus('小区域界のデータは未取込です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.small_areas);
              return;
            }

            L.geoJSON(data, {
              style: layerStyle('small_areas'),
              onEachFeature: function (feature, layer) {
                const properties = feature.properties || {};
                const name = properties.name || '';
                const population = Number(properties.population || 0).toLocaleString('ja-JP');
                const households = Number(properties.households || 0).toLocaleString('ja-JP');

                if (name) {
                  const popup = document.createElement('div');
                  const title = document.createElement('strong');
                  title.textContent = name;
                  popup.appendChild(title);

                  const detail = document.createElement('div');
                  detail.textContent = '人口 ' + population + '人 / 世帯 ' + households;
                  popup.appendChild(detail);

                  layer.bindPopup(popup);
                }

                layer.on('click', function () {
                  highlightBoundary(layer);
                });
              }
            }).addTo(layers.small_areas);

            setLayerStatus('小区域界を表示中（' + data.features.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus('小区域界のデータを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.small_areas);
          })
          .finally(function () {
            layerLoads.small_areas = null;
          });

        return layerLoads.small_areas;
      }

      function loadSchoolDistrictLayer(name) {
        if (layers[name].getLayers().length > 0) {
          return Promise.resolve();
        }

        if (layerLoads[name]) {
          return layerLoads[name];
        }

        const label = name === 'elementary' ? '小学校区' : '中学校区';
        const url = schoolDistrictUrlTemplate.replace('TYPE', name);
        const toggle = document.querySelector('.map-layer-toggle[data-layer="' + name + '"]');

        layerLoads[name] = fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (!data.features || data.features.length === 0) {
              setLayerStatus(label + 'のデータは未取込です。');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers[name]);
              return;
            }

            L.geoJSON(data, {
              style: layerStyle(name),
              onEachFeature: function (feature, layer) {
                const nameKey = name === 'elementary' ? 'A27_004' : 'A32_004';
                const providerKey = name === 'elementary' ? 'A27_002' : 'A32_002';
                const schoolName = feature.properties[nameKey] || '';
                const provider = feature.properties[providerKey] || '';

                if (schoolName) {
                  layer.bindPopup(document.createTextNode(
                    schoolName + (provider ? '（' + provider + '）' : '')
                  ));
                }

                layer.on('click', function () {
                  highlightBoundary(layer);
                });
              }
            }).addTo(layers[name]);

            setLayerStatus(label + 'を表示中（' + data.features.length + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function () {
            setLayerStatus(label + 'のデータを取得できませんでした。');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers[name]);
          })
          .finally(function () {
            layerLoads[name] = null;
          });

        return layerLoads[name];
      }

      function loadHistoricalLayer() {
        const dateElement = document.getElementById('historical-date');
        const toggle = document.querySelector('.map-layer-toggle[data-layer="historical"]');

        if (!dateElement || !toggle) {
          return Promise.resolve();
        }

        const date = dateElement.value;
        const url = historicalUrlTemplate.replace('DATE', date);
        const requestId = ++historicalRequestId;

        layers.historical.clearLayers();
        resetHistoricalNameOptions('読み込み中...');
        setLayerStatus('合併前行政界を読み込み中...');

        return fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (requestId !== historicalRequestId) {
              return;
            }

            if (!data.layers || data.layers.length === 0) {
              setLayerStatus('合併前行政界のデータは未取込です。');
              resetHistoricalNameOptions('データがありません');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.historical);
              return;
            }

            let featureCount = 0;

            data.layers.forEach(function (layer) {
              const topology = layer.topojson;

              if (!topology || !topology.objects || !topology.objects.city) {
                return;
              }

              const geojson = topojson.feature(topology, topology.objects.city);
              featureCount += (geojson.features || []).length;

              L.geoJSON(geojson, {
                style: layerStyle('historical'),
                onEachFeature: function (feature, leafletLayer) {
                  const properties = feature.properties || {};
                  const areaName = [properties.N03_003, properties.N03_004]
                    .filter(function (value) { return value; })
                    .join('');
                  const from = properties.N03_005 || '';
                  const to = properties.N03_006 || '';

                  if (areaName) {
                    const period = from ? '（' + from + '〜' + to + '）' : '';
                    leafletLayer.bindPopup(document.createTextNode(areaName + period));
                    leafletLayer.bindTooltip(document.createTextNode(areaName), {
                      sticky: true
                    });
                    historicalFeatures.push({
                      name: areaName,
                      layer: leafletLayer
                    });
                  }

                  leafletLayer.on('click', function () {
                    highlightBoundary(leafletLayer);
                  });
                }
              }).addTo(layers.historical);
            });

            if (featureCount === 0) {
              setLayerStatus('合併前行政界のデータはありません。');
              resetHistoricalNameOptions('データがありません');
              if (toggle) {
                toggle.checked = false;
              }
              map.removeLayer(layers.historical);
              return;
            }

            populateHistoricalNameOptions();
            setLayerStatus('合併前行政界 ' + date + '（' + featureCount + '件）');
            fitMapToVisibleLayers();
          })
          .catch(function (error) {
            if (requestId !== historicalRequestId) {
              return;
            }

            setLayerStatus('合併前行政界のデータを取得できませんでした。');
            resetHistoricalNameOptions('データを取得できませんでした');
            if (toggle) {
              toggle.checked = false;
            }
            map.removeLayer(layers.historical);
          });
      }

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
          const name = this.dataset.layer;

          if (this.checked) {
            map.addLayer(layers[name]);

            if (name === 'municipalities') {
              fitMapToVisibleLayers();
            } else if (name === 'historical') {
              loadHistoricalLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'small_areas') {
              loadSmallAreaLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'shelters') {
              loadShelterLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'volunteer_centers') {
              loadVolunteerCenterLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'roads') {
              loadRoadLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else if (name === 'waste_sites') {
              loadWasteSiteLayer().then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            } else {
              loadSchoolDistrictLayer(name).then(function () {
                fitMapToVisibleLayers();
                window.setTimeout(function () {
                  map.invalidateSize();
                }, 100);
              });
            }
          } else {
            map.removeLayer(layers[name]);
            fitMapToVisibleLayers();
          }

          saveLayerVisibility();
        });
      });

      const savedVisibility = loadLayerVisibility();

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        const name = toggle.dataset.layer;
        const visible = Object.prototype.hasOwnProperty.call(savedVisibility, name)
          ? Boolean(savedVisibility[name])
          : toggle.checked;

        toggle.checked = visible;

        if (visible) {
          map.addLayer(layers[name]);
        } else {
          map.removeLayer(layers[name]);
        }
      });

      const shelterActiveOnlyToggle = document.getElementById('shelter-active-only');

      if (shelterActiveOnlyToggle) {
        try {
          const storedShelterActiveOnly = window.localStorage.getItem(shelterActiveOnlyStorageKey);

          if (storedShelterActiveOnly !== null) {
            shelterActiveOnlyToggle.checked = storedShelterActiveOnly === '1';
          }
        } catch (error) {
        }

        shelterActiveOnlyToggle.addEventListener('change', function () {
          try {
            window.localStorage.setItem(shelterActiveOnlyStorageKey, this.checked ? '1' : '0');
          } catch (error) {
          }

          if (shelterLayerLoaded) {
            applyShelterFilter();
          }
        });
      }

      const historicalDate = document.getElementById('historical-date');
      const historicalDateStorageKey = 'disaster-map-historical-date';

      if (historicalDate) {
        try {
          const storedHistoricalDate = window.localStorage.getItem(historicalDateStorageKey);

          if (storedHistoricalDate && Array.prototype.some.call(historicalDate.options, function (option) {
            return option.value === storedHistoricalDate;
          })) {
            historicalDate.value = storedHistoricalDate;
          }
        } catch (error) {
        }

        historicalDate.addEventListener('change', function () {
          try {
            window.localStorage.setItem(historicalDateStorageKey, this.value);
          } catch (error) {
          }

          const toggle = document.querySelector('.map-layer-toggle[data-layer="historical"]');

          if (toggle && toggle.checked) {
            loadHistoricalLayer();
          }
        });
      }

      const historicalName = document.getElementById('historical-name');

      if (historicalName) {
        historicalName.addEventListener('change', function () {
          const feature = historicalFeatures[Number(this.value)];

          if (!feature || !feature.layer) {
            clearBoundaryHighlight();
            return;
          }

          highlightBoundary(feature.layer);

          if (feature.layer.getBounds) {
            map.fitBounds(feature.layer.getBounds(), {
              padding: [20, 20],
              maxZoom: 14
            });
          }

          feature.layer.openPopup();
        });
      }

      const municipalitiesReady = loadMunicipalitiesLayer();

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        const name = toggle.dataset.layer;

        if (!toggle.checked || name === 'municipalities') {
          return;
        }

        let loader;

        if (name === 'historical') {
          loader = loadHistoricalLayer();
        } else if (name === 'small_areas') {
          loader = loadSmallAreaLayer();
        } else if (name === 'shelters') {
          loader = loadShelterLayer();
        } else if (name === 'volunteer_centers') {
          loader = loadVolunteerCenterLayer();
        } else if (name === 'roads') {
          loader = loadRoadLayer();
        } else if (name === 'waste_sites') {
          loader = loadWasteSiteLayer();
        } else {
          loader = loadSchoolDistrictLayer(name);
        }

        loader.then(function () {
          fitMapToVisibleLayers();
          window.setTimeout(function () {
            map.invalidateSize();
          }, 100);
        });
      });

      // --- UI state, URL sharing, presets, quick layers, municipality links ---
      const state = {
        panel: 'detail',
        layers: {},
        selectedMunicipality: null,
        hoveredMunicipality: null
      };
      const initialParams = new URLSearchParams(window.location.search);
      const layerNames = Object.keys(layers);
      const presetDefinitions = {
        resident: {
          layers: ['municipalities', 'shelters', 'roads'],
          tab: 'detail'
        },
        volunteer: {
          layers: ['municipalities', 'volunteer_centers', 'waste_sites', 'roads'],
          tab: 'support'
        },
        analysis: {
          layers: ['municipalities', 'small_areas'],
          tab: 'population'
        }
      };
      let urlReady = false;
      let applyingPreset = false;
      let pendingMunicipalityFit = null;
      let toastTimer = null;

      function isMobileViewport() {
        return window.matchMedia('(max-width: 767.98px)').matches;
      }

      function setSheetState(nextState, persist) {
        if (!sidebar) {
          return;
        }

        if (!isMobileViewport()) {
          const open = sidebar.classList.contains('is-open');
          sidebar.removeAttribute('data-sheet-state');
          sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');

          if (open) {
            sidebar.removeAttribute('inert');
          } else {
            sidebar.setAttribute('inert', '');
          }

          document.querySelectorAll('.map-app-sidebar-header, .map-app-presets, .map-app-tabs, .map-app-sidebar-body').forEach(function (element) {
            element.removeAttribute('inert');
          });
          return;
        }

        mobileSheetState = ['minimized', 'half', 'full'].indexOf(nextState) >= 0 ? nextState : 'minimized';
        sidebar.dataset.sheetState = mobileSheetState;

        const open = mobileSheetState !== 'minimized';
        sidebar.classList.toggle('is-open', open);
        sidebar.removeAttribute('inert');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebarToggle && sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        document.querySelectorAll('.map-app-sidebar-header, .map-app-presets, .map-app-tabs, .map-app-sidebar-body').forEach(function (element) {
          if (open) {
            element.removeAttribute('inert');
          } else {
            element.setAttribute('inert', '');
          }
        });

        if (persist) {
          try {
            window.localStorage.setItem(sidebarStorageKey, open ? '1' : '0');
          } catch (error) {
          }
        }
      }

      function showToast(message) {
        const toast = document.getElementById('map-toast');

        if (!toast) {
          return;
        }

        toast.textContent = message;
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
          toast.hidden = true;
        }, 3200);
      }

      function syncLayerUi() {
        state.layers = {};

        document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
          state.layers[toggle.dataset.layer] = toggle.checked;
        });

        document.querySelectorAll('[data-quick-layer]').forEach(function (checkbox) {
          checkbox.checked = Boolean(state.layers[checkbox.dataset.quickLayer]);
        });

        if (!applyingPreset) {
          document.querySelectorAll('[data-map-preset]').forEach(function (button) {
            button.setAttribute('aria-pressed', 'false');
          });
        }

        updateQuickLegend();
        syncUrl();
      }

      function setLayerEnabled(name, enabled, options) {
        const toggles = document.querySelectorAll('.map-layer-toggle[data-layer="' + name + '"]');

        if (toggles.length === 0) {
          return Promise.resolve();
        }

        let changed = false;

        toggles.forEach(function (toggle) {
          if (toggle.checked !== enabled) {
            toggle.checked = enabled;
            changed = true;
          }
        });

        if (changed) {
          toggles[0].dispatchEvent(new Event('change', { bubbles: true }));
        } else {
          syncLayerUi();
        }

        return Promise.resolve();
      }

      function updateQuickLegend() {
        const container = document.getElementById('map-quick-legend');

        if (!container) {
          return;
        }

        const legend = {
          municipalities: [
            { label: '市町村境', color: '#1f3c88', line: true }
          ],
          shelters: [
            { label: '開設中', color: '#dc3545' },
            { label: '指定避難所', color: '#17a2b8' },
            { label: '指定緊急避難場所', color: '#fd7e14' }
          ],
          roads: [
            { label: '全面通行止', color: '#dc3545', line: true },
            { label: '片側交互通行', color: '#fd7e14', line: true },
            { label: '大型・重量規制', color: '#6f42c1', line: true }
          ],
          waste_sites: [
            { label: '仮置場', color: '#198754' }
          ],
          volunteer_centers: [
            { label: 'ボランティアセンター', color: '#28a745' }
          ],
          small_areas: [
            { label: '小区域界', color: '#adb5bd', line: true }
          ],
          elementary: [
            { label: '小学校区', color: '#fd7e14', line: true }
          ],
          junior_high: [
            { label: '中学校区', color: '#6f42c1', line: true }
          ],
          historical: [
            { label: '合併前行政界', color: '#495057', line: true }
          ]
        };
        const labels = {
          municipalities: '行政区域',
          shelters: '避難所',
          roads: '道路規制',
          waste_sites: '仮置場',
          volunteer_centers: 'ボランティア',
          small_areas: '小区域',
          elementary: '小学校区',
          junior_high: '中学校区',
          historical: '合併前行政界'
        };
        const rows = [];

        Object.keys(legend).forEach(function (name) {
          if (!state.layers[name]) {
            return;
          }

          legend[name].forEach(function (item) {
            rows.push(
              '<div class="map-quick-legend-row">'
              + '<span class="map-quick-legend-swatch' + (item.line ? ' is-line' : '') + '" style="background:' + item.color + '"></span>'
              + '<span>' + escapeHtml(labels[name] + '：' + item.label) + '</span>'
              + '</div>'
            );
          });
        });

        container.innerHTML = rows.length > 0
          ? rows.join('')
          : '<div class="map-quick-legend-empty">表示中の主要レイヤーはありません。</div>';
      }

      function syncUrl() {
        if (!urlReady || !mapViewInitialized) {
          return;
        }

        const params = new URLSearchParams(window.location.search);
        const center = map.getCenter();

        params.set('lat', center.lat.toFixed(5));
        params.set('lng', center.lng.toFixed(5));
        params.set('z', String(map.getZoom()));

        const enabledLayers = layerNames.filter(function (name) {
          return Boolean(state.layers[name]);
        });

        params.set('layers', enabledLayers.join(','));
        params.set('tab', state.panel);

        if (state.selectedMunicipality) {
          params.set('m', state.selectedMunicipality);
        } else {
          params.delete('m');
        }

        const query = params.toString();
        const url = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
        window.history.replaceState(null, '', url);
      }

      function copyCurrentUrl() {
        syncUrl();

        const url = window.location.href;
        const onCopied = function () {
          showToast('表示状態のURLをコピーしました。');
        };
        const fallback = function () {
          const textarea = document.createElement('textarea');
          textarea.value = url;
          textarea.setAttribute('readonly', '');
          textarea.style.position = 'fixed';
          textarea.style.opacity = '0';
          document.body.appendChild(textarea);
          textarea.select();

          try {
            document.execCommand('copy');
            onCopied();
          } catch (error) {
            showToast('URLをコピーできませんでした。');
          }

          document.body.removeChild(textarea);
        };

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(url).then(onCopied).catch(fallback);
        } else {
          fallback();
        }
      }

      function municipalityLayers(code) {
        return municipalityBoundaries.get(String(code)) || [];
      }

      function municipalityBounds(code) {
        const bounds = L.latLngBounds([]);

        municipalityLayers(code).forEach(function (boundary) {
          if (boundary.getBounds) {
            bounds.extend(boundary.getBounds());
          }
        });

        return bounds;
      }

      function refreshMunicipalityStyles() {
        municipalityBoundaries.forEach(function (boundaryLayers, code) {
          const mode = code === state.selectedMunicipality
            ? 'selected'
            : (code === state.hoveredMunicipality ? 'hover' : 'base');

          boundaryLayers.forEach(function (boundary) {
            if (!boundary.setStyle) {
              return;
            }

            if (mode === 'selected') {
              boundary.setStyle({
                color: '#b02a37',
                weight: 4,
                opacity: 1,
                fillColor: '#b02a37',
                fillOpacity: 0.12,
                dashArray: null
              });
            } else if (mode === 'hover') {
              boundary.setStyle({
                color: '#17a2b8',
                weight: 3,
                opacity: 1,
                fillColor: '#17a2b8',
                fillOpacity: 0.18,
                dashArray: null
              });
            } else {
              boundary.setStyle(boundary.saizBaseStyle || layerStyle('municipalities'));
            }

            if (mode !== 'base' && boundary.bringToFront) {
              boundary.bringToFront();
            }
          });
        });

        document.querySelectorAll('.map-municipality-tag').forEach(function (tag) {
          const code = tag.dataset.municipalityCode;
          tag.classList.toggle('is-selected', code === state.selectedMunicipality);
          tag.classList.toggle('is-hovered', code === state.hoveredMunicipality);
          tag.setAttribute('aria-pressed', code === state.selectedMunicipality ? 'true' : 'false');
        });
      }

      function setMunicipalityHover(code) {
        state.hoveredMunicipality = code;
        refreshMunicipalityStyles();
      }

      function clearMunicipalityHover(code) {
        if (state.hoveredMunicipality === code) {
          state.hoveredMunicipality = null;
          refreshMunicipalityStyles();
        }
      }

      function selectMunicipality(code, fit) {
        if (!code) {
          return;
        }

        state.selectedMunicipality = String(code);
        clearBoundaryHighlight();
        refreshMunicipalityStyles();

        if (fit) {
          const bounds = municipalityBounds(code);

          if (bounds.isValid()) {
            viewLocked = false;
            map.fitBounds(bounds, {
              padding: [40, 40],
              maxZoom: 15,
              animate: true,
              duration: 0.6
            });
          } else {
            pendingMunicipalityFit = String(code);
          }
        }

        syncUrl();
      }

      function filterMunicipalityList() {
        const input = document.getElementById('municipality-search');
        const empty = document.getElementById('municipality-empty');
        const query = input ? input.value.trim().toLowerCase() : '';
        let visibleTotal = 0;

        document.querySelectorAll('.map-municipality-group').forEach(function (group) {
          let visible = 0;

          group.querySelectorAll('.map-municipality-tag').forEach(function (tag) {
            const matches = query === '' || tag.textContent.toLowerCase().indexOf(query) >= 0;
            tag.hidden = !matches;

            if (matches) {
              visible++;
            }
          });

          group.hidden = visible === 0;
          visibleTotal += visible;
        });

        if (empty) {
          empty.hidden = visibleTotal > 0;
        }
      }

      function applyPreset(name) {
        const preset = presetDefinitions[name];

        if (!preset) {
          return;
        }

        applyingPreset = true;
        viewLocked = true;

        layerNames.forEach(function (layerName) {
          setLayerEnabled(layerName, preset.layers.indexOf(layerName) >= 0);
        });

        applyingPreset = false;
        viewLocked = false;

        document.querySelectorAll('[data-map-preset]').forEach(function (button) {
          button.setAttribute('aria-pressed', button.dataset.mapPreset === name ? 'true' : 'false');
        });

        const tab = document.querySelector('[data-map-panel="' + preset.tab + '"]');

        if (tab) {
          tab.click();
        }

        window.setTimeout(function () {
          fitMapToVisibleLayers();
        }, 80);
      }

      function locateMe() {
        if (!navigator.geolocation) {
          showToast('このブラウザでは現在地を取得できません。');
          return;
        }

        showToast('現在地を取得しています...');

        navigator.geolocation.getCurrentPosition(function (position) {
          const latlng = [position.coords.latitude, position.coords.longitude];

          if (!window.saizGpsMarker) {
            window.saizGpsMarker = L.circleMarker(latlng, {
              radius: 8,
              color: '#ffffff',
              weight: 3,
              fillColor: '#0d6efd',
              fillOpacity: 1
            }).addTo(map);
          } else {
            window.saizGpsMarker.setLatLng(latlng);
          }

          viewsafeSetView(latlng, 15);
          showToast('現在地に移動しました。');
        }, function () {
          showToast('現在地を取得できませんでした。位置情報の許可を確認してください。');
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 60000
        });
      }

      function viewsafeSetView(latlng, zoom) {
        viewLocked = false;
        map.setView(latlng, zoom, { animate: true });
        mapViewInitialized = true;
        syncUrl();
      }

      document.querySelectorAll('[data-map-panel]').forEach(function (tab) {
        tab.addEventListener('click', function () {
          state.panel = this.dataset.mapPanel;

          if (isMobileViewport() && mobileSheetState === 'minimized') {
            setSheetState('half', false);
          }

          syncUrl();
        });
      });

      document.querySelectorAll('[data-map-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
          applyPreset(this.dataset.mapPreset);
        });
      });

      document.querySelectorAll('.map-layer-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
          if (!applyingPreset) {
            document.querySelectorAll('[data-map-preset]').forEach(function (button) {
              button.setAttribute('aria-pressed', 'false');
            });
          }

          if (!applyingPreset && urlReady) {
            viewLocked = false;
          }

          syncLayerUi();
        });
      });

      document.querySelectorAll('[data-quick-layer]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
          setLayerEnabled(this.dataset.quickLayer, this.checked);
        });
      });

      const quickPanel = document.getElementById('map-quick-panel');
      const quickToggle = document.getElementById('map-quick-toggle');

      if (quickToggle) {
        quickToggle.addEventListener('click', function () {
          const collapsed = quickPanel.classList.toggle('is-collapsed');
          quickToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
      }

      if (quickPanel && isMobileViewport()) {
        quickPanel.classList.add('is-collapsed');
        quickToggle && quickToggle.setAttribute('aria-expanded', 'false');
      }

      document.querySelectorAll('.map-municipality-tag').forEach(function (tag) {
        const code = tag.dataset.municipalityCode;

        tag.addEventListener('mouseenter', function () {
          setMunicipalityHover(code);
        });
        tag.addEventListener('mouseleave', function () {
          clearMunicipalityHover(code);
        });
        tag.addEventListener('focus', function () {
          setMunicipalityHover(code);
        });
        tag.addEventListener('blur', function () {
          clearMunicipalityHover(code);
        });
        tag.addEventListener('click', function () {
          selectMunicipality(code, true);
        });
      });

      const municipalitySearch = document.getElementById('municipality-search');

      if (municipalitySearch) {
        municipalitySearch.addEventListener('input', filterMunicipalityList);
      }

      document.querySelectorAll('[data-map-panel-link]').forEach(function (button) {
        button.addEventListener('click', function () {
          const tab = document.querySelector('[data-map-panel="' + this.dataset.mapPanelLink + '"]');
          const focusTarget = this.dataset.focusTarget;

          if (tab) {
            tab.click();
          }

          if (focusTarget) {
            window.setTimeout(function () {
              const element = document.getElementById(focusTarget);

              if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.classList.add('is-focused');
                window.setTimeout(function () {
                  element.classList.remove('is-focused');
                }, 2400);
              }
            }, 80);
          }
        });
      });

      const shareButton = document.getElementById('map-share-button');
      const shareQuickButton = document.getElementById('map-share-quick-button');

      shareButton && shareButton.addEventListener('click', copyCurrentUrl);
      shareQuickButton && shareQuickButton.addEventListener('click', copyCurrentUrl);

      const geolocateButton = document.getElementById('map-geolocate-button');

      if (geolocateButton) {
        geolocateButton.addEventListener('click', locateMe);
      }

      const locateControl = L.control({ position: 'bottomright' });
      locateControl.onAdd = function () {
        const container = L.DomUtil.create('div', 'leaflet-bar');
        const button = L.DomUtil.create('button', 'map-locate-button', container);
        button.type = 'button';
        button.title = '現在地へ移動';
        button.setAttribute('aria-label', '現在地へ移動');
        button.innerHTML = '<i class="fas fa-location-arrow" aria-hidden="true"></i>';
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(button, 'click', function (event) {
          L.DomEvent.stop(event);
          locateMe();
        });

        return container;
      };
      locateControl.addTo(map);

      const sheetHandle = document.getElementById('map-sheet-handle');

      if (sheetHandle) {
        let sheetPointer = null;

        sheetHandle.addEventListener('pointerdown', function (event) {
          if (!isMobileViewport()) {
            return;
          }

          sheetPointer = {
            startY: event.clientY,
            moved: false
          };

          try {
            sheetHandle.setPointerCapture(event.pointerId);
          } catch (error) {
          }
        });

        sheetHandle.addEventListener('pointermove', function (event) {
          if (!sheetPointer) {
            return;
          }

          if (Math.abs(event.clientY - sheetPointer.startY) > 12) {
            sheetPointer.moved = true;
          }
        });

        sheetHandle.addEventListener('pointerup', function (event) {
          if (!sheetPointer) {
            return;
          }

          const delta = event.clientY - sheetPointer.startY;
          const moved = sheetPointer.moved;
          sheetPointer = null;

          if (!moved) {
            setSheetState(
              mobileSheetState === 'minimized' ? 'half' : (mobileSheetState === 'half' ? 'full' : 'minimized'),
              true
            );
            return;
          }

          if (delta < -40) {
            setSheetState(
              mobileSheetState === 'minimized' ? 'half' : 'full',
              true
            );
          } else if (delta > 40) {
            setSheetState(
              mobileSheetState === 'full' ? 'half' : 'minimized',
              true
            );
          }
        });

        sheetHandle.addEventListener('pointercancel', function () {
          sheetPointer = null;
        });

        sheetHandle.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            setSheetState(
              mobileSheetState === 'minimized' ? 'half' : (mobileSheetState === 'half' ? 'full' : 'minimized'),
              true
            );
          } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setSheetState(mobileSheetState === 'minimized' ? 'half' : 'full', true);
          } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            setSheetState(mobileSheetState === 'full' ? 'half' : 'minimized', true);
          }
        });
      }

      window.addEventListener('resize', function () {
        setSheetState(mobileSheetState, false);

        if (!isMobileViewport()) {
          quickPanel && quickPanel.classList.remove('is-collapsed');
          quickToggle && quickToggle.setAttribute('aria-expanded', 'true');
        }
      });

      map.on('moveend zoomend', function () {
        syncUrl();
      });

      const initialMunicipality = initialParams.get('m');

      municipalitiesReady.then(function () {
        refreshMunicipalityStyles();

        if (pendingMunicipalityFit) {
          const code = pendingMunicipalityFit;
          pendingMunicipalityFit = null;
          selectMunicipality(code, true);
        } else if (initialMunicipality) {
          selectMunicipality(initialMunicipality, false);
        }
      });

      const initialTab = initialParams.get('tab');

      state.panel = document.querySelector('[data-map-panel].is-active')
        ? document.querySelector('[data-map-panel].is-active').dataset.mapPanel
        : 'detail';

      const initialLayers = initialParams.get('layers');
      const initialLatValue = initialParams.get('lat');
      const initialLngValue = initialParams.get('lng');
      const initialZoomValue = initialParams.get('z');
      const initialLat = Number(initialLatValue);
      const initialLng = Number(initialLngValue);
      const initialZoom = initialZoomValue !== null && initialZoomValue !== ''
        ? Number(initialZoomValue)
        : 13;
      const hasInitialView = initialLatValue !== null
        && initialLatValue !== ''
        && initialLngValue !== null
        && initialLngValue !== ''
        && Number.isFinite(initialLat)
        && Number.isFinite(initialLng)
        && Number.isFinite(initialZoom);

      viewLocked = hasInitialView;

      if (initialLayers !== null) {
        const desiredLayers = new Set(initialLayers.split(',').filter(function (name) {
          return name !== '';
        }));

        layerNames.forEach(function (layerName) {
          setLayerEnabled(layerName, desiredLayers.has(layerName));
        });
      }

      syncLayerUi();

      if (hasInitialView) {
        map.setView([initialLat, initialLng], initialZoom);
        mapViewInitialized = true;
        viewLocked = true;
      }

      if (initialTab) {
        const initialTabButton = document.querySelector('[data-map-panel="' + initialTab + '"]');

        if (initialTabButton && initialTabButton.dataset.mapPanel !== state.panel) {
          initialTabButton.click();
        }
      }

      urlReady = true;
      syncUrl();
    });
  </script>
@endpush
