#!/usr/bin/env node

/**
 * Advanced E2E Test Runner
 * 
 * Script to run the advanced E2E tests that are disabled by default.
 * These tests cover bulk operations, file uploads, and rich text editing.
 */

const { execSync } = require('child_process');
const path = require('path');

console.log('🚀 Running Advanced E2E Tests for JTD Admin Panel');
console.log('================================================\n');

const testSuites = [
  {
    name: 'Bulk Operations',
    file: 'bulk-operations.spec.js',
    description: 'Multi-select, batch actions, and bulk operations'
  },
  {
    name: 'File Upload Workflows',
    file: 'file-upload.spec.js',
    description: 'Image uploads, document uploads, drag-and-drop'
  },
  {
    name: 'Rich Text Editing',
    file: 'rich-text-editing.spec.js',
    description: 'Markdown fields, WYSIWYG editors, formatting'
  }
];

const browsers = ['chromium', 'firefox'];
const results = {};

async function runTestSuite(suite, browser) {
  console.log(`\n📋 Running ${suite.name} tests on ${browser}...`);
  console.log(`   ${suite.description}`);
  
  try {
    const command = `npx playwright test tests/e2e/${suite.file} --project=${browser} --reporter=line`;
    const output = execSync(command, { 
      encoding: 'utf8',
      cwd: path.resolve(__dirname, '../..'),
      timeout: 120000 // 2 minutes timeout
    });
    
    console.log(`✅ ${suite.name} (${browser}): PASSED`);
    return { status: 'passed', output };
    
  } catch (error) {
    console.log(`❌ ${suite.name} (${browser}): FAILED`);
    console.log(`   Error: ${error.message.split('\n')[0]}`);
    return { status: 'failed', error: error.message };
  }
}

async function runAllTests() {
  console.log('Starting advanced test execution...\n');
  
  for (const browser of browsers) {
    console.log(`\n🌐 Testing on ${browser.toUpperCase()}`);
    console.log('='.repeat(30));
    
    results[browser] = {};
    
    for (const suite of testSuites) {
      const result = await runTestSuite(suite, browser);
      results[browser][suite.name] = result;
    }
  }
  
  // Print summary
  console.log('\n\n📊 ADVANCED TEST RESULTS SUMMARY');
  console.log('='.repeat(50));
  
  let totalTests = 0;
  let passedTests = 0;
  
  for (const browser of browsers) {
    console.log(`\n${browser.toUpperCase()}:`);
    
    for (const suite of testSuites) {
      const result = results[browser][suite.name];
      const status = result.status === 'passed' ? '✅ PASSED' : '❌ FAILED';
      console.log(`  ${suite.name}: ${status}`);
      
      totalTests++;
      if (result.status === 'passed') {
        passedTests++;
      }
    }
  }
  
  console.log('\n' + '='.repeat(50));
  console.log(`📈 Overall Results: ${passedTests}/${totalTests} tests passed`);
  console.log(`📊 Success Rate: ${Math.round((passedTests / totalTests) * 100)}%`);
  
  if (passedTests === totalTests) {
    console.log('\n🎉 All advanced tests passed! The admin panel is fully functional.');
  } else if (passedTests > totalTests * 0.7) {
    console.log('\n✅ Most advanced tests passed. Some features may need attention.');
  } else {
    console.log('\n⚠️ Many advanced tests failed. Features may not be fully implemented.');
  }
  
  console.log('\n💡 Note: These are advanced feature tests. Core functionality');
  console.log('   is tested separately in the CI-ready test suite.');
  
  // Exit with appropriate code
  process.exit(passedTests === totalTests ? 0 : 1);
}

// Handle command line arguments
const args = process.argv.slice(2);

if (args.includes('--help') || args.includes('-h')) {
  console.log('Advanced E2E Test Runner for JTD Admin Panel\n');
  console.log('Usage:');
  console.log('  node run-advanced-tests.js [options]\n');
  console.log('Options:');
  console.log('  --help, -h     Show this help message');
  console.log('  --bulk         Run only bulk operations tests');
  console.log('  --files        Run only file upload tests');
  console.log('  --rich-text    Run only rich text editing tests');
  console.log('  --browser=X    Run on specific browser (chromium, firefox, webkit)');
  console.log('\nExamples:');
  console.log('  node run-advanced-tests.js');
  console.log('  node run-advanced-tests.js --bulk --browser=chromium');
  console.log('  node run-advanced-tests.js --files');
  process.exit(0);
}

// Handle specific test selection
if (args.includes('--bulk')) {
  testSuites.splice(1, 2); // Keep only bulk operations
}
if (args.includes('--files')) {
  testSuites.splice(0, 1); // Remove bulk operations
  testSuites.splice(1, 1); // Remove rich text
}
if (args.includes('--rich-text')) {
  testSuites.splice(0, 2); // Keep only rich text
}

// Handle browser selection
const browserArg = args.find(arg => arg.startsWith('--browser='));
if (browserArg) {
  const selectedBrowser = browserArg.split('=')[1];
  if (['chromium', 'firefox', 'webkit'].includes(selectedBrowser)) {
    browsers.length = 0;
    browsers.push(selectedBrowser);
  } else {
    console.error(`❌ Invalid browser: ${selectedBrowser}`);
    console.error('Valid browsers: chromium, firefox, webkit');
    process.exit(1);
  }
}

// Run the tests
runAllTests().catch(error => {
  console.error('\n❌ Test execution failed:', error.message);
  process.exit(1);
});
